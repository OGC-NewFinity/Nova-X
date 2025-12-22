import { createRoot, useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import './style.scss';

const NovaXDashboard = () => {
    // State Variables
    const [ apiKey, setApiKey ] = useState( '' );
    const [ isKeySaved, setIsKeySaved ] = useState( false );
    const [ prompt, setPrompt ] = useState( '' );
    const [ messages, setMessages ] = useState( [] );
    const [ isLoading, setIsLoading ] = useState( false );
    const chatEndRef = useRef( null );

    // 1. On Load: Check if we have an API Key saved
    useEffect( () => {
        // We will implement a 'check-key' endpoint later, for now let's assume false
        // to force the user to enter it once.
    }, [] );

    // Auto-scroll to bottom of chat
    useEffect( () => {
        chatEndRef.current?.scrollIntoView( { behavior: "smooth" } );
    }, [ messages ] );

    // 2. Save API Key Function
    const handleSaveKey = () => {
        setIsLoading( true );
        apiFetch( {
            path: '/nova-x/v1/save-key',
            method: 'POST',
            data: { api_key: apiKey },
        } ).then( ( res ) => {
            setIsKeySaved( true );
            setIsLoading( false );
            addSystemMessage( "System connected. API Key saved securely." );
        } ).catch( ( err ) => {
            alert( 'Error saving key: ' + err.message );
            setIsLoading( false );
        } );
    };

    // 3. Send Message Function
    const handleSend = () => {
        if ( ! prompt.trim() ) return;

        // Add User Message
        const userMsg = { role: 'user', content: prompt };
        setMessages( [ ...messages, userMsg ] );
        setPrompt( '' );
        setIsLoading( true );

        // Send to Backend
        apiFetch( {
            path: '/nova-x/v1/chat',
            method: 'POST',
            data: { prompt: prompt },
        } ).then( ( res ) => {
            // Add AI Response
            const aiMsg = { role: 'ai', content: res.reply };
            setMessages( ( prev ) => [ ...prev, aiMsg ] );
            setIsLoading( false );
        } ).catch( ( err ) => {
            const errorMsg = { role: 'system', content: "Error: " + err.message };
            setMessages( ( prev ) => [ ...prev, errorMsg ] );
            setIsLoading( false );
        } );
    };

    // Helper: Add System Message
    const addSystemMessage = ( text ) => {
        setMessages( ( prev ) => [ ...prev, { role: 'system', content: text } ] );
    };

    return (
        <div className="nova-x-dashboard">
            {/* --- Header --- */}
            <header className="nova-header">
                <div className="logo-area">
                    <h1>🚀 Nova-X <span className="version-badge">v0.1</span></h1>
                </div>
                <div className="status-area">
                    <span className={`status-dot ${isKeySaved ? 'online' : 'offline'}`}></span>
                    { isKeySaved ? 'System Online' : 'Setup Required' }
                </div>
            </header>
            
            <div className="nova-body">
                
                {/* --- Sidebar (Visual Commander) --- */}
                <aside className="nova-sidebar">
                    <h3>Visual Commander</h3>
                    <div className="control-group">
                        <label>Active Theme</label>
                        <div className="fake-input">No Theme Generated</div>
                    </div>
                    { ! isKeySaved && (
                        <div className="api-setup-box">
                            <h4>⚠️ Setup</h4>
                            <p>Enter OpenAI API Key:</p>
                            <input 
                                type="password" 
                                value={apiKey} 
                                onChange={ (e) => setApiKey(e.target.value) } 
                                placeholder="sk-..."
                            />
                            <button onClick={handleSaveKey} disabled={isLoading}>
                                { isLoading ? 'Saving...' : 'Connect' }
                            </button>
                        </div>
                    )}
                </aside>

                {/* --- Main Chat Area --- */}
                <main className="nova-chat-area">
                    <div className="chat-history">
                        { messages.length === 0 && (
                            <div className="welcome-message">
                                <h2>Welcome to OGC Nova-X</h2>
                                <p>I am your AI Architect. Ask me to build a theme.</p>
                            </div>
                        )}
                        { messages.map( ( msg, index ) => (
                            <div key={index} className={`chat-bubble ${msg.role}`}>
                                <div className="bubble-content">
                                    { msg.role === 'ai' && <span className="avatar">🤖</span> }
                                    { msg.role === 'user' && <span className="avatar">👤</span> }
                                    <p>{msg.content}</p>
                                </div>
                            </div>
                        ))}
                        { isLoading && <div className="loading-dots">Thinking...</div> }
                        <div ref={chatEndRef} />
                    </div>

                    <div className="chat-input-zone">
                        <textarea 
                            value={prompt}
                            onChange={(e) => setPrompt(e.target.value)}
                            onKeyDown={(e) => { if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); } }}
                            placeholder="Describe your dream website..."
                            disabled={!isKeySaved || isLoading}
                        />
                        <button onClick={handleSend} disabled={!isKeySaved || isLoading}>
                            ➤
                        </button>
                    </div>
                </main>
            </div>
        </div>
    );
};

// Mount the App
document.addEventListener( 'DOMContentLoaded', () => {
    const container = document.getElementById( 'nova-x-app-root' );
    if ( container ) {
        const root = createRoot( container );
        root.render( <NovaXDashboard /> );
    }
} );