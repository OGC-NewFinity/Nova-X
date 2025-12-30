/**
 * Logo Diagnostic Script
 * Analyzes the Nova-X logo file for validity and compatibility
 */

const fs = require('fs');
const path = require('path');
const http = require('http');
const https = require('https');

// File paths
const logoPath = path.join(__dirname, '..', 'nova-x', 'assets', 'images', 'logo', 'nova-x-logo-crystal-primary.png');
const expectedPath = path.join(__dirname, '..', 'assets', 'images', 'logo', 'nova-x-logo-crystal-primary.png');

// Check if sharp is available (optional for advanced analysis)
let sharp = null;
try {
    sharp = require('sharp');
} catch (e) {
    console.log('Note: sharp library not available, using basic PNG header parsing');
}

/**
 * Read PNG file header to validate format and get basic info
 */
function parsePNGHeader(filePath) {
    const buffer = fs.readFileSync(filePath);
    
    // PNG signature: 89 50 4E 47 0D 0A 1A 0A
    const pngSignature = Buffer.from([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A]);
    const header = buffer.slice(0, 8);
    
    if (!header.equals(pngSignature)) {
        return { valid: false, error: 'Invalid PNG signature' };
    }
    
    // Read IHDR chunk (chunk type + data starts at byte 16)
    // IHDR chunk structure: length (4 bytes) + type "IHDR" (4 bytes) + data (13 bytes) + CRC (4 bytes)
    // Data: width (4), height (4), bit depth (1), color type (1), compression (1), filter (1), interlace (1)
    const width = buffer.readUInt32BE(16);
    const height = buffer.readUInt32BE(20);
    const bitDepth = buffer[24];
    const colorType = buffer[25];
    const compression = buffer[26];
    const filter = buffer[27];
    const interlace = buffer[28];
    
    // Color type meanings:
    // 0 = Grayscale
    // 2 = RGB
    // 3 = Indexed (palette)
    // 4 = Grayscale with alpha
    // 6 = RGBA
    const colorTypeNames = {
        0: 'Grayscale',
        2: 'RGB',
        3: 'Indexed (Palette)',
        4: 'Grayscale with Alpha',
        6: 'RGBA'
    };
    
    // Check for color profiles in additional chunks
    let hasSRGB = false;
    let hasICC = false;
    let hasColorProfile = false;
    
    // Scan chunks for color profile info
    let offset = 33; // After IHDR chunk
    while (offset < buffer.length - 8) {
        const chunkLength = buffer.readUInt32BE(offset);
        const chunkType = buffer.toString('ascii', offset + 4, offset + 8);
        
        if (chunkType === 'sRGB') {
            hasSRGB = true;
        }
        if (chunkType === 'iCCP') {
            hasICC = true;
            hasColorProfile = true;
        }
        if (chunkType === 'cHRM') {
            hasColorProfile = true;
        }
        
        offset += 12 + chunkLength; // length + type + data + CRC
        if (offset >= buffer.length) break;
    }
    
    return {
        valid: true,
        width,
        height,
        bitDepth,
        colorType: colorTypeNames[colorType] || `Unknown (${colorType})`,
        hasAlpha: colorType === 4 || colorType === 6,
        compression,
        filter,
        interlace,
        hasSRGB,
        hasICC,
        hasColorProfile,
        isCMYK: false // PNG doesn't support CMYK natively
    };
}

/**
 * Get file MIME type using magic bytes
 */
function getMIMEType(filePath) {
    const buffer = fs.readFileSync(filePath, { encoding: null });
    const header = buffer.slice(0, 8);
    
    // PNG signature
    const pngSig = Buffer.from([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A]);
    if (header.equals(pngSig)) {
        return 'image/png';
    }
    
    return 'unknown';
}

/**
 * Generate diagnostic report
 */
function generateReport() {
    const report = {
        timestamp: new Date().toISOString(),
        fileInfo: {},
        formatDetails: {},
        pathAnalysis: {},
        issues: [],
        suggestions: []
    };
    
    // Check if file exists
    const fileExists = fs.existsSync(logoPath);
    const expectedExists = fs.existsSync(expectedPath);
    
    if (!fileExists) {
        report.issues.push(`Logo file not found at: ${logoPath}`);
        return report;
    }
    
    // File info
    const stats = fs.statSync(logoPath);
    report.fileInfo = {
        path: logoPath,
        relativePath: path.relative(process.cwd(), logoPath),
        exists: true,
        size: stats.size,
        sizeFormatted: `${(stats.size / 1024).toFixed(2)} KB`,
        modified: stats.mtime.toISOString(),
        permissions: stats.mode.toString(8).slice(-3)
    };
    
    // Expected path check
    report.pathAnalysis = {
        actualPath: logoPath,
        expectedPath: expectedPath,
        expectedPathExists: expectedExists,
        codeExpectsPath: 'assets/images/logo/nova-x-logo-crystal-primary.png',
        pathMismatch: !expectedExists && fileExists
    };
    
    if (report.pathAnalysis.pathMismatch) {
        report.issues.push(`Path mismatch: Code expects logo at 'assets/images/logo/' but file is at 'nova-x/assets/images/logo/'`);
    }
    
    // Format validation
    const mimeType = getMIMEType(logoPath);
    report.formatDetails.mimeType = mimeType;
    
    if (mimeType !== 'image/png') {
        report.issues.push(`Invalid MIME type: Expected 'image/png', got '${mimeType}'`);
        return report;
    }
    
    // PNG header parsing
    const pngInfo = parsePNGHeader(logoPath);
    
    if (!pngInfo.valid) {
        report.issues.push(`PNG validation failed: ${pngInfo.error}`);
        return report;
    }
    
    report.formatDetails = {
        ...report.formatDetails,
        ...pngInfo,
        dimensions: `${pngInfo.width} × ${pngInfo.height} pixels`,
        aspectRatio: (pngInfo.width / pngInfo.height).toFixed(2)
    };
    
    // Dimension warnings
    if (pngInfo.width > 1024) {
        report.issues.push(`Logo width (${pngInfo.width}px) exceeds recommended maximum of 1024px`);
        report.suggestions.push('Consider resizing logo to reduce file size and improve load time');
    }
    
    if (pngInfo.height > 1024) {
        report.issues.push(`Logo height (${pngInfo.height}px) exceeds recommended maximum of 1024px`);
    }
    
    // File size warning
    if (stats.size > 500 * 1024) {
        report.issues.push(`File size (${report.fileInfo.sizeFormatted}) is quite large for a logo`);
        report.suggestions.push('Consider optimizing the PNG file using tools like TinyPNG or ImageOptim');
    }
    
    // Color profile warnings
    if (!pngInfo.hasSRGB && !pngInfo.hasICC) {
        report.suggestions.push('No color profile detected - ensure sRGB color space for web compatibility');
    }
    
    // Use sharp for advanced analysis if available
    if (sharp) {
        try {
            const metadata = sharp(logoPath).metadata();
            report.formatDetails.sharpMetadata = {
                format: metadata.format,
                width: metadata.width,
                height: metadata.height,
                channels: metadata.channels,
                hasAlpha: metadata.hasAlpha,
                space: metadata.space,
                hasProfile: metadata.hasProfile,
                isOpaque: metadata.isOpaque
            };
            
            if (metadata.space && metadata.space !== 'srgb') {
                report.issues.push(`Color space is '${metadata.space}' - recommend sRGB for web use`);
            }
        } catch (e) {
            report.issues.push(`Sharp analysis failed: ${e.message}`);
        }
    }
    
    // PHP path simulation
    const pluginRoot = path.join(__dirname, '..');
    const simulatedPath = path.join(pluginRoot, 'assets', 'images', 'logo', 'nova-x-logo-crystal-primary.png');
    report.pathAnalysis.phpSimulation = {
        pluginDir: pluginRoot,
        expectedRelativePath: 'assets/images/logo/nova-x-logo-crystal-primary.png',
        simulatedFullPath: simulatedPath,
        simulatedPathExists: fs.existsSync(simulatedPath),
        actualFileRelativePath: path.relative(pluginRoot, logoPath).replace(/\\/g, '/')
    };
    
    return report;
}

// Run diagnostics
const report = generateReport();

// Output as JSON for parsing
console.log(JSON.stringify(report, null, 2));

