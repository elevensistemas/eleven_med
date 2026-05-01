@extends('layouts.admin')

@section('title', 'Chat IT - Eleven Med AI')
@section('subtitle', 'Asistente Inteligente con acceso a tus métricas clínicas')

@section('content')
<style>
/* Chat IT Custom Styles */
.chat-container {
    height: 70vh;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    border: 1px solid #eee;
    overflow: hidden;
}
.chat-history {
    flex-grow: 1;
    overflow-y: auto;
    padding: 24px;
    background: #fdfdfd;
}
.chat-input-area {
    padding: 20px;
    background: #fff;
    border-top: 1px solid #f0f0f0;
}
.msg {
    margin-bottom: 20px;
    max-width: 80%;
}
.msg-user {
    margin-left: auto;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 12px 18px;
    border-radius: 18px 18px 0 18px;
    box-shadow: 0 2px 10px rgba(94, 106, 210, 0.2);
}
.msg-bot {
    margin-right: auto;
    background: #f1f3f4;
    color: #333;
    padding: 18px;
    border-radius: 18px 18px 18px 0;
    line-height: 1.5;
}
.msg-bot pre {
    background: #2b2b2b;
    color: #fff;
    padding: 10px;
    border-radius: 8px;
    margin-top: 10px;
}
.msg-bot code {
    color: #d14;
    background: #fff;
    padding: 2px 4px;
    border-radius: 4px;
}
.typing-indicator span {
    display: inline-block;
    width: 6px;
    height: 6px;
    background-color: #999;
    border-radius: 50%;
    margin-right: 3px;
    animation: typing 1.4s infinite ease-in-out both;
}
.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

@keyframes listeningPulse {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.mic-listening {
    animation: listeningPulse 1.5s infinite;
    background: #dc3545 !important;
    color: white !important;
}

/* Mariana Avatar Styles */
.mariana-wrapper {
    position: relative; width: 220px; height: 220px; margin: 0 auto; margin-top: 40px;
}
.mariana-img {
    width: 100%; height: 100%; object-fit: cover; border-radius: 50%;
    border: 5px solid #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    position: relative; z-index: 10;
}
.mariana-glow {
    position: absolute; top: -15px; left: -15px; right: -15px; bottom: -15px;
    border-radius: 50%; background: radial-gradient(circle, rgba(94, 106, 210, 0.6) 0%, rgba(94, 106, 210, 0) 70%);
    z-index: 1; opacity: 0; transition: opacity 0.2s;
}

/* Physics Engines */
@keyframes marianaBreathe {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-6px) scale(1.02); }
}

@keyframes wordBoundaryPulse {
    0% { transform: scaleY(1) translateY(0); }
    50% { transform: scaleY(1.05) translateY(4px); }
    100% { transform: scaleY(1) translateY(0); }
}

.mariana-idle .mariana-img { animation: marianaBreathe 4s infinite ease-in-out; }
.mariana-speaking .mariana-img { border-color: var(--primary-color); }
.mariana-speaking .mariana-glow { opacity: 1; animation: listeningPulse 0.3s infinite alternate; }

.word-pulse .mariana-img {
    animation: wordBoundaryPulse 0.15s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
}

/* Theme Dark Support for Chat IA Page */
body.theme-dark .main-content .chat-container {
    background-color: #111827 !important;
    border-color: rgba(255,255,255,0.08) !important;
}
body.theme-dark .main-content .chat-history {
    background-color: transparent !important;
}
body.theme-dark .main-content .chat-input-area {
    background-color: #111827 !important;
    border-color: rgba(255,255,255,0.08) !important;
}
body.theme-dark .main-content .msg-bot {
    background-color: #1e293b !important;
    color: #e0e0e0 !important;
}
body.theme-dark .main-content .bg-light,
body.theme-dark .main-content .bg-white {
    background-color: #111827 !important;
    border-color: rgba(255,255,255,0.08) !important;
    color: #fff !important;
}
body.theme-dark .main-content #chatInput {
    background-color: #1e293b !important;
    color: #fff !important;
}
body.theme-dark .main-content #chatInput::placeholder {
    color: #94a3b8 !important;
}
body.theme-dark .main-content .text-dark {
    color: #fff !important;
}
body.theme-dark .main-content .card,
body.theme-dark .main-content .bg-white {
    background-color: #111827 !important;
    border-color: rgba(255,255,255,0.08) !important;
}
</style>

<div class="row" id="mainRow">
    <div class="col-lg-10 mx-auto transition-all duration-300" id="chatColumn">
        <div class="chat-container">
            <!-- Header -->
            <div class="bg-light px-4 py-3 border-bottom d-flex align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                    <i class="bi bi-robot fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Chat IA</h5>
                    <small class="text-success"><i class="bi bi-circle-fill text-success" style="font-size: 0.6rem;"></i> En línea - RAG sincronizado</small>
                </div>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <input type="hidden" id="assistantSelector" value="Mariana">
                    
                    <button class="btn btn-primary fw-bold rounded-pill shadow d-flex align-items-center" id="marianaToggle" title="Activar Interfaz Visual">
                        <i class="bi bi-person-video me-2"></i> Invocar Asistente
                    </button>
                    <!-- Mantengo este hidden por compatibilidad del state isVoiceActive -->
                    <button class="d-none" id="voiceToggle"></button>
                </div>
            </div>

            <!-- Chat History -->
            <div class="chat-history" id="chatArea">
                <div class="msg msg-bot shadow-sm">
                    Hola Doctor/a <strong>{{ Auth::user()->name }}</strong>. Soy Chat IA, el asistente inteligente de Eleven Med.<br><br>
                    Tengo acceso a tus estadísticas en tiempo real. Puedes preguntarme cosas como:
                    <ul class="mb-0 mt-2">
                        <li>¿Cuántas consultas atendí este mes?</li>
                        <li>¿Cuáles son mis patologías o diagnósticos más recetados?</li>
                        <li>¿Cómo viene ocupada mi agenda en los próximos 7 días?</li>
                    </ul>
                </div>
            </div>

            <!-- Input Area -->
            <div class="chat-input-area">
                <form id="chatForm" class="d-flex position-relative">
                    <input type="text" id="chatInput" class="form-control form-control-lg border-0 bg-light shadow-none" style="border-radius: 30px; padding-right: 90px;" placeholder="Escribe o dicta tu consulta aquí..." autocomplete="off" required>
                    
                    <button type="button" id="micBtn" class="btn btn-light rounded-circle position-absolute text-muted shadow-sm" style="right: 50px; top: 5px; width: 38px; height: 38px; padding:0; border:1px solid #ddd;" title="Dictar por Voz">
                        <i class="bi bi-mic-fill"></i>
                    </button>

                    <button type="submit" id="sendBtn" class="btn btn-primary rounded-circle position-absolute" style="right: 5px; top: 5px; width: 38px; height: 38px; padding:0; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;" title="Enviar a la IA">
                        <i class="bi bi-send-fill text-white"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="text-center mt-3">
            <small class="text-muted"><i class="bi bi-shield-lock-fill text-success"></i> La información médica viaja encriptada como volumetría genérica.</small>
        </div>
    </div>
    
    <!-- Visual Column -->
    <div class="col-lg-4 d-none text-center" id="marianaColumn">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white d-flex align-items-center justify-content-center p-4">
            <h4 class="fw-bold mb-0 text-dark" id="assistantTitle">Secretaria Inteligente</h4>
            <p class="text-primary mb-2"><i class="bi bi-magic"></i> <span id="assistantSubtitle">Asistente IA Activa</span></p>
            
            <div class="mariana-wrapper mariana-idle" id="marianaSilouhette">
                <img src="{{ asset('images/mariana_avatar.png') }}" class="mariana-img" alt="Asistente" id="assistantImagePreview">
                <div class="mariana-glow"></div>
            </div>
            
            <div class="mt-5 text-muted small">
                <i class="bi bi-soundwave fs-3 d-block mb-2" id="sndIndicator"></i>
                <span id="marianaStatusText">Escuchando el entorno...</span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatArea = document.getElementById('chatArea');
    const sendBtn = document.getElementById('sendBtn');
    
    // Voice Control DOM elements
    const micBtn = document.getElementById('micBtn');
    const voiceToggle = document.getElementById('voiceToggle');

    // Voice Variables
    let isVoiceActive = true; // Auto-play audio is ON by default
    let wasLastMessageVoice = false; 
    let recognition;

    // Initialize Web Speech API for Dictation (Speech-to-Text)
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    micBtn.addEventListener('click', () => {
        if (!SpeechRecognition) {
            alert('🎙️ Tu navegador bloquea el uso del micrófono porque el sistema no tiene un certificado HTTPS, o estás accediendo mediante una dirección IP (Ej: 192.168...). Para usar la voz, debes ingresar mediante http://localhost o tener HTTPS activo.');
            return;
        }
        
        if (!recognition) {
            recognition = new SpeechRecognition();
            recognition.lang = 'es-AR'; // Español Rioplatense o Genérico
            recognition.continuous = false;
            recognition.interimResults = false;

            recognition.onstart = function() {
                micBtn.classList.add('mic-listening');
                chatInput.placeholder = 'Escuchando... hable ahora.';
                wasLastMessageVoice = true; // Flag that input is coming from voice
            };

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                chatInput.value = transcript;
                // Submit form automatically after dictation
                if(transcript.trim() !== '') {
                    chatForm.dispatchEvent(new Event('submit'));
                }
            };

            recognition.onerror = function(event) {
                console.error('Error de reconocimiento:', event.error);
                resetMicUI();
            };

            recognition.onend = function() {
                resetMicUI();
            };
        }
        
        if(window.speechSynthesis) {
            window.speechSynthesis.cancel(); // Silenciar al bot si está hablando
        }
        recognition.start();
    });

    function resetMicUI() {
        micBtn.classList.remove('mic-listening');
        chatInput.placeholder = 'Escribe o dicta tu consulta aquí...';
    }

    const marianaToggle = document.getElementById('marianaToggle');
    const marianaColumn = document.getElementById('marianaColumn');
    const chatColumn = document.getElementById('chatColumn');
    const assistantSelector = document.getElementById('assistantSelector');
    let isMarianaActive = false;

    // TTS Voices Setup
    let voices = [];
    let marianaVoice = null;

    function loadVoices() {
        voices = window.speechSynthesis.getVoices();
        marianaVoice = voices.find(v => v.name.includes('Sabina') || v.name.includes('Monica') || v.name.includes('Helena') || (v.lang.includes('es') && v.name.toLowerCase().includes('female')) || v.name === 'Google español');
    }
    
    if (speechSynthesis.onvoiceschanged !== undefined) {
        speechSynthesis.onvoiceschanged = loadVoices;
    }
    loadVoices();

    // Toggle Assistant Avatar
    marianaToggle.addEventListener('click', () => {
        isMarianaActive = !isMarianaActive;
        isVoiceActive = isMarianaActive; // Sync voice state internally
        
        if(isMarianaActive) {
            marianaToggle.innerHTML = '<i class="bi bi-x-circle me-1"></i> Desactivar Asistente';
            marianaToggle.classList.replace('btn-outline-primary', 'btn-danger');
            chatColumn.classList.replace('col-lg-10', 'col-lg-8');
            setTimeout(() => { marianaColumn.classList.remove('d-none'); }, 150);
        } else {
            marianaToggle.innerHTML = '<i class="bi bi-person-video me-1"></i> Invocar Asistente';
            marianaToggle.classList.replace('btn-danger', 'btn-outline-primary');
            marianaColumn.classList.add('d-none');
            chatColumn.classList.replace('col-lg-8', 'col-lg-10');
            window.speechSynthesis.cancel();
        }
    });

    // Speak Text (Text-to-Speech)
    function speakText(text) {
        if (!isVoiceActive && !wasLastMessageVoice && !isMarianaActive) return;

        let cleanText = text.replace(/[*#`_\-]/g, '').trim();
        const utterance = new SpeechSynthesisUtterance(cleanText);
        utterance.lang = 'es-ES';
        
        if (marianaVoice) utterance.voice = marianaVoice;
        utterance.rate = 1.05;
        utterance.pitch = 1.1; // Femenino Default

        // VTuber Illusion Engine hooks
        utterance.onstart = function() {
            const wrapper = document.getElementById('marianaSilouhette');
            if(wrapper) {
                wrapper.classList.replace('mariana-idle', 'mariana-speaking');
                document.getElementById('marianaStatusText').innerText = "Te está hablando...";
                document.getElementById('sndIndicator').classList.replace('bi-soundwave', 'bi-mic-fill');
                document.getElementById('sndIndicator').classList.add('text-primary');
            }
        };

        // Word Boundary Lipsync Anim
        utterance.onboundary = function(e) {
            if(e.name === 'word') {
                const wrapper = document.getElementById('marianaSilouhette');
                if(wrapper) {
                    wrapper.classList.remove('word-pulse');
                    void wrapper.offsetWidth; // Trigger reflow
                    wrapper.classList.add('word-pulse');
                }
            }
        };

        const stopMariana = function() {
            const wrapper = document.getElementById('marianaSilouhette');
            if(wrapper) {
                wrapper.classList.remove('word-pulse');
                wrapper.classList.replace('mariana-speaking', 'mariana-idle');
                document.getElementById('marianaStatusText').innerText = "Escuchando el entorno...";
                document.getElementById('sndIndicator').classList.replace('bi-mic-fill', 'bi-soundwave');
                document.getElementById('sndIndicator').classList.remove('text-primary');
            }
        };

        utterance.onend = stopMariana;
        utterance.onerror = stopMariana;

        window.speechSynthesis.speak(utterance);
    }

    function appendMessage(text, isUser = false) {
        const div = document.createElement('div');
        div.className = `msg ${isUser ? 'msg-user' : 'msg-bot shadow-sm'}`;
        
        if(isUser) {
            div.textContent = text;
        } else {
            // Parse CHART format: [CHART:pie|Pacientes por OS|OSDE:10,Particular:5]
            let chartMatch = text.match(/\[CHART:(pie|bar|doughnut)\|([^\|]+)\|([^\]]+)\]/);
            let chartHtml = '';
            if (chartMatch) {
                let cType = chartMatch[1];
                let cTitle = chartMatch[2];
                let cDataRaw = chartMatch[3];
                let labels = [];
                let dataValues = [];
                cDataRaw.split(',').forEach(pair => {
                    let parts = pair.split(':');
                    if (parts.length === 2) {
                        labels.push(parts[0].trim());
                        dataValues.push(parseInt(parts[1].trim()));
                    }
                });
                
                let canvasId = 'aiChart_' + Date.now() + Math.floor(Math.random() * 1000);
                chartHtml = `<div class="mt-3 bg-white p-2 rounded border shadow-sm"><canvas id="${canvasId}" style="max-height: 250px;"></canvas></div>`;
                
                // Remove the [CHART...] tag from the text
                text = text.replace(chartMatch[0], '').trim();
                
                // Render the chart after the element is injected
                setTimeout(() => {
                    let canvas = document.getElementById(canvasId);
                    if(canvas) {
                        let ctx = canvas.getContext('2d');
                        new Chart(ctx, {
                            type: cType,
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: cTitle,
                                    data: dataValues,
                                    backgroundColor: ['#5e6ad2', '#20c997', '#fd7e14', '#e83e8c', '#6f42c1', '#17a2b8', '#ffc107', '#28a745'],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10 } } },
                                    title: { display: true, text: cTitle, font: { size: 12 } }
                                }
                            }
                        });
                    }
                }, 300);
            }
            
            let feedbackHtml = `
                <div class="feedback-actions mt-2 d-flex justify-content-end gap-2" style="opacity: 0.4; transition: opacity 0.2s;">
                    <button type="button" class="btn btn-sm p-0 border-0 bg-transparent text-secondary" onclick="submitFeedback(this, 'up')" title="Buena respuesta"><i class="bi bi-hand-thumbs-up fs-6"></i></button>
                    <button type="button" class="btn btn-sm p-0 border-0 bg-transparent text-secondary" onclick="submitFeedback(this, 'down')" title="Mala respuesta"><i class="bi bi-hand-thumbs-down fs-6"></i></button>
                </div>
            `;
            
            div.innerHTML = (text ? marked.parse(text) : '') + chartHtml + feedbackHtml;
            
            div.onmouseover = () => { let fb = div.querySelector('.feedback-actions'); if(fb) fb.style.opacity = '1'; };
            div.onmouseout = () => { let fb = div.querySelector('.feedback-actions'); if(fb) fb.style.opacity = '0.4'; };
        }

        chatArea.appendChild(div);
        chatArea.scrollTop = chatArea.scrollHeight;
        return div;
    }

    function appendTyping() {
        const div = document.createElement('div');
        div.className = 'msg msg-bot typing-indicator shadow-sm';
        div.id = 'typingBubble';
        div.innerHTML = '<span></span><span></span><span></span>';
        chatArea.appendChild(div);
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    window.submitFeedback = function(btn, type) {
        let parent = btn.closest('.feedback-actions');
        parent.innerHTML = type === 'up' 
            ? '<span class="text-success small fw-bold"><i class="bi bi-hand-thumbs-up-fill"></i> Excelente</span>' 
            : '<span class="text-danger small fw-bold"><i class="bi bi-hand-thumbs-down-fill"></i> Malo</span>';
        
        let feedbackMsg = type === 'up' 
            ? "💡 [SISTEMA ALERTA INTERNA]: El usuario marcó tu respuesta anterior como EXCELENTE. Sigue usando ese formato, precisión o tono. Fue de gran utilidad." 
            : "⚠️ [SISTEMA ALERTA INTERNA]: El usuario marcó tu respuesta anterior como MALA o errónea. Evita repetir ese patrón, discúlpate en tu próxima interacción y busca otra forma de responder.";
            
        window.aiChatHistory.push({ role: "system", content: feedbackMsg });
        if(window.aiChatHistory.length > 20) window.aiChatHistory = window.aiChatHistory.slice(-20);
        localStorage.setItem(AI_STORAGE_KEY, JSON.stringify(window.aiChatHistory));
    };

    function removeTyping() {
        const bubble = document.getElementById('typingBubble');
        if (bubble) bubble.remove();
    }

    let isSubmitting = false;
    let lastUserQuestion = '';

    const AI_STORAGE_KEY = 'elevenmed_ai_chat_history';
    window.aiChatHistory = JSON.parse(localStorage.getItem(AI_STORAGE_KEY)) || [];

    // Cargar historial de memoria compartida al iniciar
    document.addEventListener('DOMContentLoaded', () => {
        if (window.aiChatHistory.length > 0) {
            // Limpiar área por si acaso
            chatArea.innerHTML = '';
            window.aiChatHistory.forEach(msg => {
                appendMessage(msg.content, msg.role === 'user');
            });
            chatArea.scrollTop = chatArea.scrollHeight;
        } else {
            // Mensaje de bienvenida inicial
            appendMessage("¡Hola! Soy Mariana, la Inteligencia Artificial Clínica de Eleven Med. ¿En qué puedo ayudarte hoy?", false);
        }
    });

    // Flecha arriba para repetir última pregunta
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (lastUserQuestion !== '') {
                chatInput.value = lastUserQuestion;
            } else {
                // Si no hay variable local, buscar en el historial la última pregunta
                const lastMsg = window.aiChatHistory.slice().reverse().find(m => m.role === 'user');
                if (lastMsg) chatInput.value = lastMsg.content;
            }
        }
    });

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting) return; // Prevent double taps or multiple mic events
        
        const prompt = chatInput.value.trim();
        if (!prompt) return;

        isSubmitting = true;
        lastUserQuestion = prompt; // Guardar pregunta

        // User message
        appendMessage(prompt, true);
        
        window.aiChatHistory.push({ role: "user", content: prompt });
        if(window.aiChatHistory.length > 20) window.aiChatHistory = window.aiChatHistory.slice(-20); // Limit context
        localStorage.setItem(AI_STORAGE_KEY, JSON.stringify(window.aiChatHistory));

        chatInput.value = '';
        sendBtn.disabled = true;
        
        appendTyping();

        // Extraer historial sin incluir la última pregunta porque el backend ya la anexa como "prompt"
        // Wait! The backend appends "prompt" separately, so we just send the history without the current prompt!
        // But we already pushed the prompt to window.aiChatHistory.
        // So we should send slice(0, -1) as history!
        const historyToSend = window.aiChatHistory.slice(0, -1);

        axios.post('{{ route("chatit.ask") }}', { 
            prompt: prompt, 
            history: historyToSend
        }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            removeTyping();
            
            const reply = res.data.reply;
            appendMessage(reply, false);
            
            window.aiChatHistory.push({ role: "assistant", content: reply });
            if(window.aiChatHistory.length > 20) window.aiChatHistory = window.aiChatHistory.slice(-20);
            localStorage.setItem(AI_STORAGE_KEY, JSON.stringify(window.aiChatHistory));
            
            // Hablar Respuesta IA
            speakText(reply);
            
            // Reinicio flags
            wasLastMessageVoice = false;
        })
        .catch(err => {
            removeTyping();
            let errorMsg = 'Error al comunicarse con la IA.';
            if(err.response && err.response.data && err.response.data.error) {
                errorMsg = err.response.data.error;
            }
            appendMessage(`⚠️ ${errorMsg}`, false);
            speakText('Lo siento, ha ocurrido un error de conexión con la inteligencia artificial.');
            wasLastMessageVoice = false;
        })
        .finally(() => {
            sendBtn.disabled = false;
            isSubmitting = false;
            chatInput.focus();
        });
    });
</script>
@endsection
