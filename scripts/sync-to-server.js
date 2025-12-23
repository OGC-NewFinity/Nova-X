const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../.env') });
const { execSync } = require('child_process');
const fs = require('fs');

// Get server details from environment variables
const SERVER_USER = process.env.NOVA_X_SERVER_USER || 'YOUR_USERNAME';
const SERVER_IP = process.env.NOVA_X_SERVER_IP || 'YOUR_SERVER_IP';
const SERVER_PATH = process.env.NOVA_X_SERVER_PATH || '/public_html/wp-content/plugins/nova-x';

// Check if credentials are set
if (!process.env.NOVA_X_SERVER_USER || !process.env.NOVA_X_SERVER_IP || !process.env.NOVA_X_SERVER_PATH) {
    console.error('\n❌ Error: Server credentials not configured!');
    console.error('Please set the following environment variables:');
    console.error('  NOVA_X_SERVER_USER=your_username');
    console.error('  NOVA_X_SERVER_IP=your_server_ip');
    console.error('  NOVA_X_SERVER_PATH=/public_html/wp-content/plugins/nova-x\n');
    process.exit(1);
}

const projectRoot = path.resolve(__dirname, '..');

// Remote path for SCP (using absolute path exactly as provided in .env)
const remotePath = `${SERVER_USER}@${SERVER_IP}:${SERVER_PATH}`;

console.log('\n🚀 Uploading files to server...');
console.log(`   Target: ${SERVER_USER}@${SERVER_IP}:${SERVER_PATH}\n`);

try {
    // Create remote directory structure using SSH
    console.log('📁 Creating remote directory structure...');
    try {
        const mkdirCommand = `ssh -o ConnectTimeout=10 ${SERVER_USER}@${SERVER_IP} "mkdir -p ${SERVER_PATH}"`;
        console.log(`   Running: ${mkdirCommand}`);
        execSync(mkdirCommand, {
            stdio: 'inherit',
            cwd: projectRoot
        });
        console.log('   ✅ Remote directory created/verified\n');
    } catch (sshError) {
        console.error('\n❌ Failed to create remote directory:');
        console.error('   SSH Error Details:');
        if (sshError.stdout) console.error(sshError.stdout.toString());
        if (sshError.stderr) console.error(sshError.stderr.toString());
        console.error('   Error message:', sshError.message);
        throw sshError;
    }

    // Validate and build if necessary
    const buildPathLocal = path.join(projectRoot, 'build');
    
    // Check if build folder exists
    if (!fs.existsSync(buildPathLocal)) {
        console.log('📦 Build folder not found. Running npm run build...\n');
        try {
            execSync('npm run build', {
                stdio: 'inherit',
                cwd: projectRoot
            });
            console.log('\n✅ Build completed successfully!\n');
        } catch (buildError) {
            console.error('\n❌ Build failed:', buildError.message);
            if (buildError.stdout) console.error(buildError.stdout.toString());
            if (buildError.stderr) console.error(buildError.stderr.toString());
            process.exit(1);
        }
    }
    
    // Upload build folder
    console.log('📦 Uploading build folder...');
    // Convert Windows path to forward slashes for SCP
    const buildPathForward = buildPathLocal.replace(/\\/g, '/');
    const remoteBuildPath = `${remotePath}/build`;
    
    console.log(`   Local path: ${buildPathForward}`);
    console.log(`   Remote path: ${remoteBuildPath}`);
    try {
        execSync(`scp -o ConnectTimeout=10 -r "${buildPathForward}" "${remoteBuildPath}"`, {
            stdio: 'inherit',
            cwd: projectRoot
        });
    } catch (scpError) {
        console.error('\n❌ SCP upload failed for build folder:');
        console.error('   SCP Error Details:');
        if (scpError.stdout) console.error(scpError.stdout.toString());
        if (scpError.stderr) console.error(scpError.stderr.toString());
        console.error('   Error message:', scpError.message);
        throw scpError;
    }

    // Upload nova-x.php specifically
    console.log('\n📄 Uploading nova-x.php...');
    const novaXPhp = path.join(projectRoot, 'nova-x.php');
    // Convert Windows path to forward slashes for SCP
    const novaXPhpForward = novaXPhp.replace(/\\/g, '/');
    const remoteNovaXPhp = `${remotePath}/nova-x.php`;
    
    if (fs.existsSync(novaXPhp)) {
        console.log(`   Local path: ${novaXPhpForward}`);
        console.log(`   Remote path: ${remoteNovaXPhp}`);
        try {
            execSync(`scp -o ConnectTimeout=10 "${novaXPhpForward}" "${remoteNovaXPhp}"`, {
                stdio: 'inherit',
                cwd: projectRoot
            });
        } catch (scpError) {
            console.error('\n❌ SCP upload failed for nova-x.php:');
            console.error('   SCP Error Details:');
            if (scpError.stdout) console.error(scpError.stdout.toString());
            if (scpError.stderr) console.error(scpError.stderr.toString());
            console.error('   Error message:', scpError.message);
            throw scpError;
        }
    } else {
        console.log(`   ⚠️  File not found: ${novaXPhpForward}`);
    }

    // Upload inc folder (exclude node_modules)
    console.log('\n📁 Uploading inc folder...');
    const incPath = path.join(projectRoot, 'inc');
    // Convert Windows path to forward slashes for SCP
    const incPathForward = incPath.replace(/\\/g, '/');
    const remoteIncPath = `${remotePath}/inc`;
    
    console.log(`   Local path: ${incPathForward}`);
    console.log(`   Remote path: ${remoteIncPath}`);
    try {
        execSync(`scp -o ConnectTimeout=10 -r "${incPathForward}" "${remoteIncPath}"`, {
            stdio: 'inherit',
            cwd: projectRoot
        });
    } catch (scpError) {
        console.error('\n❌ SCP upload failed for inc folder:');
        console.error('   SCP Error Details:');
        if (scpError.stdout) console.error(scpError.stdout.toString());
        if (scpError.stderr) console.error(scpError.stderr.toString());
        console.error('   Error message:', scpError.message);
        throw scpError;
    }

    console.log('\n✅ Files uploaded successfully!\n');
} catch (error) {
    console.error('\n❌ Upload failed:');
    console.error('   Full Error Details:');
    if (error.stdout) console.error('   STDOUT:', error.stdout.toString());
    if (error.stderr) console.error('   STDERR:', error.stderr.toString());
    console.error('   Error message:', error.message);
    if (error.stack) console.error('   Stack trace:', error.stack);
    process.exit(1);
}

