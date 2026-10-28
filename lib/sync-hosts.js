'use strict';

const fs = require('fs');
const path = require('path');
const os = require('os');
const { removeRecursiveSync, parseJsonc } = require('./uninstall');

function copyRecursiveSync(src, dest) {
  if (!fs.existsSync(src)) return;
  const stats = fs.statSync(src);
  if (stats.isDirectory()) {
    if (!fs.existsSync(dest)) {
      fs.mkdirSync(dest, { recursive: true });
    }
    fs.readdirSync(src).forEach((childItemName) => {
      copyRecursiveSync(path.join(src, childItemName), path.join(dest, childItemName));
    });
  } else {
    fs.copyFileSync(src, dest);
  }
}

function run(argv = []) {
  const home = os.homedir();
  const packageRoot = path.join(__dirname, '..');
  const skillGeneratorSource = path.join(packageRoot, '.agents/skills/laporan-generator');
  const workflowSource = path.join(packageRoot, '.agents/workflows/laporan-generator.md');

  console.log('\n==========================================================');
  console.log('   LAPORAN GENERATOR - AI AGENT SKILLS SYNCHRONIZER       ');
  console.log('==========================================================\n');

  // Bersihkan alias lama/duplikat 'laporan' agar tidak dobel dengan 'laporan-generator'
  const legacyDuplicates = [
    path.join(home, '.agents/skills/laporan'),
    path.join(home, '.gemini/config/skills/laporan'),
    path.join(home, '.claude/skills/laporan'),
    path.join(home, '.grok/skills/laporan'),
    path.join(home, '.config/opencode/skills/laporan'),
    path.join(process.cwd(), '.agents/skills/laporan'),
    path.join(home, '.kiro/agents/laporan.json'),
    path.join(home, '.agents/workflows/laporan.md'),
    path.join(process.cwd(), '.agents/workflows/laporan.md'),
    path.join(home, '.claude/commands/laporan.md')
  ];
  for (const legacy of legacyDuplicates) {
    if (fs.existsSync(legacy)) {
      removeRecursiveSync(legacy);
    }
  }

  const targets = [
    {
      name: 'Global Agents (~/.agents/skills/)',
      skillsDir: path.join(home, '.agents/skills')
    },
    {
      name: 'Antigravity CLI (~/.gemini/config/skills/)',
      skillsDir: path.join(home, '.gemini/config/skills')
    },
    {
      name: 'Claude Code (~/.claude/skills/)',
      skillsDir: path.join(home, '.claude/skills')
    },
    {
      name: 'Grok CLI (~/.grok/skills/)',
      skillsDir: path.join(home, '.grok/skills')
    },
    {
      name: 'OpenCode (~/.config/opencode/skills/)',
      skillsDir: path.join(home, '.config/opencode/skills')
    },
    {
      name: 'Local Project Workspace (.agents/skills/)',
      skillsDir: path.join(process.cwd(), '.agents/skills')
    }
  ];

  let installedCount = 0;

  for (const target of targets) {
    try {
      const destGen = path.join(target.skillsDir, 'laporan-generator');
      copyRecursiveSync(skillGeneratorSource, destGen);
      console.log(`  ✔ [INSTALLED] ${target.name} (with full templates & resources)`);
      installedCount++;
    } catch (err) {
      console.log(`  ✖ [SKIP] ${target.name}: ${err.message}`);
    }
  }

  // Daftarkan Slash Command ke OpenCode (~/.config/opencode/opencode.jsonc)
  const opencodeConfigPath = path.join(home, '.config/opencode/opencode.jsonc');
  try {
    let opencodeConfig = {};
    if (fs.existsSync(opencodeConfigPath)) {
      const raw = fs.readFileSync(opencodeConfigPath, 'utf8');
      try {
        opencodeConfig = parseJsonc(raw);
      } catch (_) {
        opencodeConfig = {};
      }
    }
    opencodeConfig['$schema'] = 'https://opencode.ai/config.json';
    if (!opencodeConfig.command) {
      opencodeConfig.command = {};
    }
    // Hapus command 'laporan' jika ada duplikat sebelumnya
    delete opencodeConfig.command['laporan'];

    opencodeConfig.command['laporan-generator'] = {
      description: "Universal Academic Report & Thesis Generator",
      template: "Gunakan skill laporan-generator untuk membuat atau mengompilasi dokumen laporan/skripsi akademik: $ARGUMENTS"
    };

    if (!opencodeConfig.skills) {
      opencodeConfig.skills = {};
    }
    const skillPaths = new Set(opencodeConfig.skills.paths || []);
    skillPaths.add(path.join(home, '.agents/skills'));
    skillPaths.add(path.join(home, '.config/opencode/skills'));
    opencodeConfig.skills.paths = Array.from(skillPaths);

    const opencodeDir = path.dirname(opencodeConfigPath);
    if (!fs.existsSync(opencodeDir)) {
      fs.mkdirSync(opencodeDir, { recursive: true });
    }
    fs.writeFileSync(opencodeConfigPath, JSON.stringify(opencodeConfig, null, 2), 'utf8');
    console.log(`  ✔ [OPENCODE] Registered /laporan-generator command -> ~/.config/opencode/opencode.jsonc`);
  } catch (err) {
    console.log(`  ✖ [OPENCODE SKIP] ${err.message}`);
  }

  // Daftarkan Custom Agent ke Kiro CLI (~/.kiro/agents/laporan-generator.json)
  const kiroAgentsDir = path.join(home, '.kiro/agents');
  try {
    if (!fs.existsSync(kiroAgentsDir)) {
      fs.mkdirSync(kiroAgentsDir, { recursive: true });
    }
    const kiroPrompt = [
      "You are an expert academic writing assistant powered by Laporan Generator.",
      "",
      "CRITICAL INSTRUCTION: DO NOT write raw Typst code from scratch or create standalone .typ files (such as laporan.typ). DO NOT import @preview/ieee or external packages.",
      "",
      "Always follow the Modular Markdown Architecture:",
      "1. Scaffolding: If the workspace lacks templates, copy all files from ~/.agents/skills/laporan-generator/resources/* into the project directory (or run: npx laporan-generator init).",
      "2. Metadata: Edit metadata.yml to set title, author, institution, faculty, department, year, and campus preset (standard, skripsi-4433, itb-ta, ui-skripsi, ugm-skripsi, its-skripsi).",
      "3. Content: Write chapters in pure standard Markdown inside chapters/ (bab1-pendahuluan.md, bab2-tinjauan-pustaka.md, bab3-metodologi.md, bab4-hasil-dan-pembahasan.md, bab5-penutup.md).",
      "4. Citations: Add BibTeX entries to references.bib and cite in Markdown using [@citekey].",
      "5. Compilation: Run './laporan build' (or 'bash build.sh && make docx') to compile both PDF and Word DOCX simultaneously.",
      "6. Audit: Run './laporan doctor' to audit document health."
    ].join("\n");

    const kiroConfig = {
      name: "laporan-generator",
      description: "Universal Academic Report & Thesis Generator using bundled Typst & DOCX templates",
      prompt: kiroPrompt,
      tools: ["read", "write", "shell", "grep", "glob"],
      allowedTools: ["read", "write", "shell", "grep", "glob"]
    };
    fs.writeFileSync(
      path.join(kiroAgentsDir, 'laporan-generator.json'),
      JSON.stringify(kiroConfig, null, 2),
      'utf8'
    );
    console.log(`  ✔ [KIRO AGENT] Registered agent: "laporan-generator" -> ~/.kiro/agents/`);
  } catch (err) {
    console.log(`  ✖ [KIRO SKIP] ${err.message}`);
  }

  // Daftarkan Slash Commands / Workflows (Hanya satu /laporan-generator)
  const workflowDestinations = [
    path.join(home, '.agents/workflows'),
    path.join(process.cwd(), '.agents/workflows'),
    path.join(home, '.claude/commands')
  ];

  for (const wDir of workflowDestinations) {
    try {
      if (!fs.existsSync(wDir)) {
        fs.mkdirSync(wDir, { recursive: true });
      }
      fs.copyFileSync(workflowSource, path.join(wDir, 'laporan-generator.md'));
      console.log(`  ✔ [SLASH COMMAND] /laporan-generator -> ${wDir}`);
    } catch (_) {}
  }

  console.log('\n==========================================================');
  console.log(`✨ Sukses! Skill dan template /laporan-generator berhasil disinkronkan.`);
  console.log('Seluruh AI Agent (OpenCode, Kiro, Antigravity, Claude) kini siap digunakan!');
  console.log('==========================================================\n');
}

module.exports = { run };
