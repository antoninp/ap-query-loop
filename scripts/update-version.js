#!/usr/bin/env node
/**
 * Release helper: updates plugin & package versions and prepends changelog entry in README.
 *
 * Workflow:
 * 1. Edit version + summary + changes in version.json.
 * 2. Run: `npm run release` (or `node scripts/update-version.js --tag`).
 * 3. Review changes, commit, push.
 * 4. Tag pushed (auto if --tag) triggers GitHub Action release packaging.
 *
 * version.json schema:
 * {
 *   "version": "1.2.3",            // required semver X.Y.Z
 *   "summary": "Short title",      // optional summary for heading line
 *   "changes": ["Bullet 1", ...], // required array (at least one)
 *   "date": "2025-11-14"          // optional ISO date; auto-generated if missing
 * }
 */
const fs = require('fs');
const path = require('path');
const cp = require('child_process');

const root = path.resolve(__dirname, '..');
const VERSION_FILE = path.join(root, 'version.json');
const PLUGIN_FILE = path.join(root, 'ap-query-loop.php');
const PACKAGE_FILE = path.join(root, 'package.json');
const README_FILE = path.join(root, 'README.md');
const TAG_ARG = process.argv.includes('--tag');
const BUILD_ARG = process.argv.includes('--build');
const PUSH_ARG = process.argv.includes('--push');
// Optional: specify remote with --remote origin (defaults to origin if unset)
function getArgValue(flag){
  const idx = process.argv.indexOf(flag);
  if(idx === -1) return null;
  const next = process.argv[idx+1];
  if(next && !next.startsWith('--')) return next;
  const eqIdx = process.argv.findIndex(a => a.startsWith(flag+'='));
  if(eqIdx !== -1){
    return process.argv[eqIdx].split('=')[1];
  }
  return null;
}
const REMOTE_ARG = getArgValue('--remote');
const AUTO_SUMMARY = process.argv.includes('--auto-summary');
const AUTO_CHANGES = process.argv.includes('--auto-changes');
const STABLE_TAG = process.argv.includes('--stable');
const DRY_RUN = process.argv.includes('--dry-run');

function exitError(msg){
  console.error('❌ ' + msg);
  process.exit(1);
}

function loadJSON(file){
  try { return JSON.parse(fs.readFileSync(file, 'utf8')); }
  catch(e){ exitError('Failed to read '+file+': '+e.message); }
}

const data = loadJSON(VERSION_FILE);
const version = data.version && String(data.version).trim();
if(!version || !/^\d+\.\d+\.\d+$/.test(version)){
  exitError('Invalid or missing semver version in version.json (expected X.Y.Z)');
}
let summary = (data.summary || '').trim();
const date = (data.date || new Date().toISOString().slice(0,10));
const changes = Array.isArray(data.changes) ? data.changes.filter(c => c && String(c).trim().length) : [];
if(changes.length === 0 && !AUTO_CHANGES){
  exitError('`changes` array in version.json must have at least one non-empty item (or use --auto-changes)');
}

console.log(`🔄 Preparing release ${version}`);

// Git helpers
function git(cmd){
  try { return cp.execSync(cmd,{stdio:'pipe'}).toString().trim(); } catch(e){ exitError('Git command failed: '+cmd+'\n'+e.message); }
}
let inRepo = true;
try { cp.execSync('git rev-parse --is-inside-work-tree',{stdio:'ignore'}); } catch(e){ inRepo=false; }

// Auto summary & changes from Conventional Commits
function getCommitsSinceLastTag(){
  if(!inRepo) return [];
  let lastTag = null;
  try { lastTag = git('git describe --tags --abbrev=0'); } catch(e){ /* ignore */ }
  let rangeCmd = 'git log --pretty=format:%s';
  if(lastTag) rangeCmd = `git log ${lastTag}..HEAD --pretty=format:%s`;
  const out = git(rangeCmd);
  return out ? out.split('\n').filter(Boolean) : [];
}

function parseConventional(commits){
  const typeMap = { feat:'Features', fix:'Fixes', perf:'Performance', refactor:'Refactors', docs:'Docs', style:'Style', test:'Tests', build:'Build', ci:'CI', chore:'Chores', revert:'Reverts' };
  const present = new Set();
  const bullets = [];
  const re = /^(feat|fix|perf|refactor|docs|style|test|build|ci|chore|revert)(!?)(\([^)]*\))?:\s*(.+)$/i;
  commits.forEach(s => {
    const m = s.match(re);
    if(m){ present.add(m[1].toLowerCase()); bullets.push(m[0]); }
  });
  const phrases = Array.from(present).map(t => typeMap[t]).filter(Boolean);
  return { phrases, bullets };
}

if((AUTO_SUMMARY || AUTO_CHANGES) && inRepo){
  const commits = getCommitsSinceLastTag();
  const { phrases, bullets } = parseConventional(commits);
  if(AUTO_SUMMARY){
    if(phrases.length){
      const lastTag = (()=>{ try { return git('git describe --tags --abbrev=0'); } catch(e){ return null; } })();
      const phrase = phrases.length === 1 ? phrases[0] : phrases.slice(0,-1).join(', ') + ' and ' + phrases.slice(-1);
      summary = `${phrase} since ${lastTag ? lastTag : 'last release'}`;
      console.log('📝 Auto-summary:', summary);
    } else if(!summary){
      exitError('Unable to auto-generate summary (no conventional commits found). Provide `summary` in version.json or use meaningful commit messages.');
    }
  }
  if(AUTO_CHANGES && bullets.length){
    // Use commit subjects as bullet points; de-duplicate and trim
    const uniq = Array.from(new Set(bullets)).map(s => s.trim());
    data.changes = uniq;
  }
}

if(!summary){
  exitError('`summary` in version.json is required (or use --auto-summary)');
}

// 1. Update plugin header Version:
let pluginSrc = fs.readFileSync(PLUGIN_FILE,'utf8');
const pluginRe = /(\*\s*Version:\s*)([\d.]+)(.*)/;
if(!pluginRe.test(pluginSrc)){
  console.warn('⚠️ Could not find Version header in plugin file');
} else {
  const replaced = pluginSrc.replace(pluginRe, (m,prefix,old,rest) => prefix+version+rest);
  if(!DRY_RUN){ fs.writeFileSync(PLUGIN_FILE, replaced, 'utf8'); }
  console.log('✅ Updated plugin header version');
}

// 2. Update package.json version
const pkg = loadJSON(PACKAGE_FILE);
const oldPkgVersion = pkg.version;
pkg.version = version;
if(!DRY_RUN){ fs.writeFileSync(PACKAGE_FILE, JSON.stringify(pkg,null,2)+'\n','utf8'); }
console.log(`✅ package.json version ${oldPkgVersion} → ${version}`);

// 3. Prepend changelog entry in README.md under ## Changelog
let readme = fs.readFileSync(README_FILE,'utf8');
const changelogHeaderRe = /(^##\s+Changelog\s*$)/im;
if(!changelogHeaderRe.test(readme)){
  console.log('ℹ️ No Changelog section found; creating one at end');
  readme += '\n\n## Changelog\n';
}

// Skip if version heading already exists (avoid duplicate entries)
const versionHeadingRe = new RegExp(`^###\\s+${version}\\b`, 'm');
if(versionHeadingRe.test(readme)){
  console.log(`ℹ️ Changelog already contains entry for ${version}; skipping prepend.`);
} else {
  // Build entry text
  const headingLine = `### ${version} - ${summary || date}`;
  const bullets = changes.map(c => `- ${c.replace(/\n+/g,' ').trim()}`).join('\n');
  const entry = `\n${headingLine}\n${bullets}\n`;
    readme = readme.replace(changelogHeaderRe, (match) => match + entry);
    if(!DRY_RUN){ fs.writeFileSync(README_FILE, readme, 'utf8'); }
    console.log('✅ Prepended changelog entry to README');
}

// 4. (Optional) Update WordPress.org readme.txt Stable tag
if(STABLE_TAG){
  const readmeTxt = path.join(root, 'readme.txt');
  if(fs.existsSync(readmeTxt)){
    let txt = fs.readFileSync(readmeTxt,'utf8');
    if(/(^Stable tag:\s*).*$/im.test(txt)){
      txt = txt.replace(/(^Stable tag:\s*).*$/im, `$1${version}`);
    } else {
      // Insert near top
      txt = `Stable tag: ${version}\n` + txt;
    }
    if(!DRY_RUN){ fs.writeFileSync(readmeTxt, txt, 'utf8'); }
    console.log('✅ Updated readme.txt Stable tag');
  } else {
    console.log('ℹ️ readme.txt not found; skipping Stable tag update');
  }
}
if(inRepo){
  if(BUILD_ARG){
    console.log('🏗️  Running build before commit/tag');
    try {
      cp.execSync('npm run build',{stdio:'inherit'});
    } catch(e){ exitError('Build failed: '+e.message); }
  }
  const status = git('git status --porcelain');
  if(status){
    git(`git add "${PLUGIN_FILE}" "${PACKAGE_FILE}" "${README_FILE}" "${VERSION_FILE}"`);
    if(STABLE_TAG){
      const readmeTxt = path.join(root,'readme.txt');
      if(fs.existsSync(readmeTxt)) git(`git add "${readmeTxt}"`);
    }
    if(!DRY_RUN){
      git(`git commit -m "chore(release): v${version}"`);
      console.log('✅ Git commit created');
    } else {
      console.log('🧪 Dry run: skipping git commit');
    }
  } else {
    console.log('ℹ️ No changes staged (files already up to date)');
  }
  if(TAG_ARG){
    if(!DRY_RUN){
      git(`git tag -a v${version} -m "Release v${version}"`);
      console.log('🏷️  Git tag v'+version+' created');
    } else {
      console.log('🧪 Dry run: skipping git tag creation');
    }
  } else {
    console.log('🏷️  Skipping tag (use --tag to create)');
  }
  // Optionally push commit and tag
  if(PUSH_ARG){
    if(!DRY_RUN){
      const remote = REMOTE_ARG || 'origin';
      try {
        // Try pushing to upstream (if configured) with tags
        console.log('🚀 Pushing commit and tags (using --follow-tags)');
        cp.execSync('git push --follow-tags', { stdio: 'inherit' });
      } catch (e) {
        try {
          // Fallback: set upstream to remote for current branch, then push with tags
          const currentBranch = git('git rev-parse --abbrev-ref HEAD');
          console.log(`ℹ️ No upstream set. Setting upstream to ${remote}/${currentBranch}`);
          cp.execSync(`git push -u ${remote} ${currentBranch}`, { stdio: 'inherit' });
          cp.execSync(`git push ${remote} --follow-tags`, { stdio: 'inherit' });
        } catch (e2) {
          // Final fallback: push tag explicitly (in case commit already exists remotely)
          if(TAG_ARG){
            const tagName = `v${version}`;
            console.log(`⚠️ Falling back to direct tag push: ${remote} ${tagName}`);
            try {
              cp.execSync(`git push ${remote} ${tagName}`, { stdio: 'inherit' });
            } catch(e3){
              exitError('Failed to push tag: '+e3.message);
            }
          } else {
            exitError('Failed to push changes. Ensure a remote is configured or use --remote <name>.');
          }
        }
      }
      console.log('✅ Push complete');
    } else {
      console.log('🧪 Dry run: skipping git push');
    }
  } else {
    console.log('🚫 Skipping push (use --push to push commits/tags)');
  }
} else {
  console.log('ℹ️ Not in a git repository; skipping commit/tag');
}

console.log('🎉 Release processing complete');