import { createRoot, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import './style.scss';

const NovaXDashboard = () => {
    const [activeTab, setActiveTab] = useState('architect');
    const [apiKey, setApiKey] = useState(window.novaXData?.apiKey || '');

    const saveSettings = () => {
        apiFetch({
            path: '/nova-x/v1/save-key',
            method: 'POST',
            data: { api_key: apiKey },
        }).then((response) => {
            console.log('Save Key Response:', response);
            alert('Settings Saved Successfully!');
        }).catch((err) => {
            console.error('Save Key Error:', err);
            alert('Error saving settings.');
        });
    };

    return (
        <div className="nova-dashboard-wrapper">
            {/* Left Sidebar */}
            <aside className="nova-sidebar">
                <div className="sidebar-brand">
                    <h2>Nova-X</h2>
                    <span>v0.1.2</span>
                </div>
                <nav className="sidebar-nav">
                    <button 
                        className={activeTab === 'architect' ? 'active' : ''} 
                        onClick={() => setActiveTab('architect')}
                    >
                        <span className="icon">🏗️</span> Architect
                    </button>
                    <button 
                        className={activeTab === 'design' ? 'active' : ''} 
                        onClick={() => setActiveTab('design')}
                    >
                        <span className="icon">🎨</span> Design
                    </button>
                    <button 
                        className={activeTab === 'settings' ? 'active' : ''} 
                        onClick={() => setActiveTab('settings')}
                    >
                        <span className="icon">⚙️</span> Settings
                    </button>
                </nav>
            </aside>

            {/* Main Content Area */}
            <main className="nova-main-content">
                <div className="content-card">
                    {activeTab === 'architect' && (
                        <div className="tab-section">
                            <h1>Theme Architect</h1>
                            <p>Describe the website you want to build, and Nova-X will generate the blocks.</p>
                            <textarea placeholder="e.g., Build a modern portfolio for a photographer..." />
                            <button className="primary-btn">Generate</button>
                        </div>
                    )}

                    {activeTab === 'design' && (
                        <div className="tab-section">
                            <h1>Design System</h1>
                            <p>Global colors, typography, and spacing controls.</p>
                        </div>
                    )}

                    {activeTab === 'settings' && (
                        <div className="tab-section">
                            <h1>Plugin Settings</h1>
                            <div className="settings-field">
                                <label>OpenAI API Key</label>
                                <input 
                                    type="password" 
                                    value={apiKey} 
                                    onChange={(e) => setApiKey(e.target.value)} 
                                    placeholder="sk-..." 
                                />
                                <p className="description">Enter your API key to enable AI theme generation.</p>
                                <button onClick={saveSettings} className="save-btn">Save Key</button>
                            </div>
                        </div>
                    )}
                </div>
            </main>
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('nova-x-app-root');
    if (root) {
        createRoot(root).render(<NovaXDashboard />);
    }
});