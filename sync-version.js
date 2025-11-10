#!/usr/bin/env node
/**
 * Sync version from version.json to ap-query-loop.php and package.json
 * Usage: node sync-version.js
 */

const fs = require('fs');
const path = require('path');

const VERSION_FILE = path.join(__dirname, 'version.json');
const PLUGIN_FILE = path.join(__dirname, 'ap-query-loop.php');
const PACKAGE_FILE = path.join(__dirname, 'package.json');

// Read version from version.json
const versionData = JSON.parse(fs.readFileSync(VERSION_FILE, 'utf8'));
const newVersion = versionData.version;

if (!newVersion || !/^\d+\.\d+\.\d+$/.test(newVersion)) {
  console.error('❌ Invalid version in version.json. Expected format: X.Y.Z');
  process.exit(1);
}

console.log(`🔄 Syncing version to ${newVersion}...`);

// Update ap-query-loop.php
let phpContent = fs.readFileSync(PLUGIN_FILE, 'utf8');
const phpUpdated = phpContent.replace(
  /(\*\s+Version:\s+)[\d.]+/,
  `$1${newVersion}`
);

if (phpUpdated === phpContent) {
  console.warn('⚠️  No Version header found in ap-query-loop.php');
} else {
  fs.writeFileSync(PLUGIN_FILE, phpUpdated, 'utf8');
  console.log(`✅ Updated ap-query-loop.php to ${newVersion}`);
}

// Update package.json
const pkgData = JSON.parse(fs.readFileSync(PACKAGE_FILE, 'utf8'));
const oldPkgVersion = pkgData.version;
pkgData.version = newVersion;
fs.writeFileSync(PACKAGE_FILE, JSON.stringify(pkgData, null, 2) + '\n', 'utf8');

if (oldPkgVersion !== newVersion) {
  console.log(`✅ Updated package.json from ${oldPkgVersion} to ${newVersion}`);
} else {
  console.log(`✅ package.json already at ${newVersion}`);
}

console.log('🎉 Version sync complete!');
