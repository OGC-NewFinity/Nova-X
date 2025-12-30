/**
 * Logo Optimization Script
 * Optimizes the Nova-X logo PNG file to reduce file size
 * 
 * Usage: node scripts/optimize-logo.js
 */

const fs = require('fs');
const path = require('path');

const logoPath = path.join(__dirname, '..', 'assets', 'images', 'logo', 'nova-x-logo-crystal-primary.png');
const backupPath = path.join(__dirname, '..', 'assets', 'images', 'logo', 'nova-x-logo-crystal-primary.backup.png');

// Check if sharp is available
let sharp = null;
try {
    sharp = require('sharp');
} catch (e) {
    console.error('ERROR: sharp library is not installed.');
    console.error('Please install it by running: npm install --save-dev sharp');
    console.error('');
    console.error('Alternatively, you can optimize the logo manually using:');
    console.error('  - Online: https://tinypng.com/');
    console.error('  - CLI: pngquant --quality=65-80 ' + logoPath);
    process.exit(1);
}

if (!fs.existsSync(logoPath)) {
    console.error('ERROR: Logo file not found at:', logoPath);
    process.exit(1);
}

// Get original file size
const originalStats = fs.statSync(logoPath);
const originalSize = originalStats.size;
const originalSizeKB = (originalSize / 1024).toFixed(2);

console.log('Optimizing logo file...');
console.log('Original size:', originalSizeKB, 'KB');

// Create backup
if (!fs.existsSync(backupPath)) {
    fs.copyFileSync(logoPath, backupPath);
    console.log('Backup created:', path.basename(backupPath));
}

// Optimize using sharp
sharp(logoPath)
    .png({
        quality: 80,
        compressionLevel: 9,
        palette: true, // Use palette if possible
    })
    .toBuffer()
    .then((data) => {
        const optimizedSize = data.length;
        const optimizedSizeKB = (optimizedSize / 1024).toFixed(2);
        const reduction = ((1 - optimizedSize / originalSize) * 100).toFixed(1);
        
        console.log('Optimized size:', optimizedSizeKB, 'KB');
        console.log('Reduction:', reduction + '%');
        
        // Only save if we achieved significant reduction and it's under 150KB
        if (optimizedSize < originalSize && optimizedSize <= 150 * 1024) {
            fs.writeFileSync(logoPath, data);
            console.log('');
            console.log('✅ Logo optimized successfully!');
            console.log('   Target achieved: ≤150KB');
            
            if (optimizedSize > 150 * 1024) {
                console.warn('⚠️  Warning: Optimized file is still above 150KB. Consider further optimization.');
            }
        } else if (optimizedSize >= originalSize) {
            console.log('');
            console.log('⚠️  Warning: Optimization did not reduce file size. Keeping original.');
            console.log('   You may need to manually optimize using TinyPNG or similar tools.');
        } else {
            fs.writeFileSync(logoPath, data);
            console.log('');
            console.log('✅ Logo optimized!');
            console.log('⚠️  Note: File size is still above 150KB. Further optimization may be needed.');
        }
    })
    .catch((error) => {
        console.error('ERROR: Failed to optimize logo:', error.message);
        
        // Restore backup if optimization failed
        if (fs.existsSync(backupPath)) {
            fs.copyFileSync(backupPath, logoPath);
            console.log('Original file restored from backup.');
        }
        
        process.exit(1);
    });

