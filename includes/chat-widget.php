<!-- Discord Support Chat Widget -->
<style>
.chat-widget-button{position:fixed!important;bottom:2rem!important;right:2rem!important;width:60px!important;height:60px!important;background:#F72585!important;border-radius:50%!important;display:flex!important;align-items:center;justify-content:center;cursor:pointer!important;box-shadow:0 10px 25px -5px rgba(247,37,133,.4)!important;transition:all .3s cubic-bezier(.4,0,.2,1);z-index:999999!important;border:none!important}
.chat-widget-button:hover{transform:translateY(-2px) scale(1.05);box-shadow:0 15px 30px -5px rgba(247,37,133,.5)!important;background:#d91a6f!important}
.chat-widget-button svg{width:28px;height:28px;fill:#fff}
.chat-notification-badge{position:absolute;top:-4px;right:-4px;background:#10b981;color:#fff;border-radius:50%;min-width:22px;height:22px;padding:0 6px;font-size:.75rem;display:flex;align-items:center;justify-content:center;font-weight:700;border:2px solid #1a1a2e;transform:scale(0);transition:transform .2s}
.chat-notification-badge.active{transform:scale(1)}
.chat-widget{position:fixed!important;bottom:calc(2rem + 76px)!important;right:2rem!important;width:380px;height:600px;max-height:calc(100vh - 120px);background:#1a1a2e;border-radius:20px;box-shadow:0 25px 50px -12px rgba(0,0,0,.5);display:none;flex-direction:column;z-index:999998!important;overflow:hidden;border:1px solid rgba(247,37,133,.2);opacity:0;transform:translateY(20px) scale(.95);transition:all .3s}
.chat-widget.active{display:flex;opacity:1;transform:translateY(0) scale(1)}
.chat-header{background:linear-gradient(135deg,#F72585,#d91a6f);padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between}
.chat-header-info{display:flex;align-items:center;gap:12px}
.chat-header-avatar{width:42px;height:42px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff}
.chat-header-text h3{font-size:1rem;font-weight:600;color:#fff;margin:0 0 2px}
.chat-header-status{font-size:.8rem;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px}
.chat-status-dot{width:8px;height:8px;background:#10b981;border-radius:50%;box-shadow:0 0 0 2px rgba(16,185,129,.3)}
.chat-status-dot.offline{background:#ef4444;box-shadow:0 0 0 2px rgba(239,68,68,.3)}
.chat-close{background:rgba(255,255,255,.1);border:none;color:#fff;cursor:pointer;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all .2s}
.chat-close:hover{background:rgba(255,255,255,.2)}
.chat-user-form-container{flex:1;padding:1.5rem;background:#1a1a2e;overflow-y:auto;display:none}
.chat-user-form-container.active{display:block}
.chat-form-title{font-size:1.25rem;font-weight:700;color:#fff;margin-bottom:.5rem}
.chat-form-subtitle{font-size:.9rem;color:#9ca3af;margin-bottom:1.5rem;line-height:1.5}
.chat-form-group{margin-bottom:1.25rem}
.chat-form-group label{display:block;font-size:.875rem;font-weight:500;color:#e5e7eb;margin-bottom:.5rem}
.chat-form-group .required{color:#F72585}
.chat-form-group input,.chat-form-group textarea{width:100%;padding:.875rem 1rem;background:#0f0f1a;border:1px solid rgba(247,37,133,.2);border-radius:12px;font-size:.95rem;color:#fff;outline:none;transition:all .2s}
.chat-form-group input::placeholder,.chat-form-group textarea::placeholder{color:#6b7280}
.chat-form-group input:focus,.chat-form-group textarea:focus{border-color:#F72585;box-shadow:0 0 0 3px rgba(247,37,133,.1)}
.chat-form-group textarea{resize:none;min-height:80px}
.chat-form-submit{width:100%;padding:1rem;background:#F72585;color:#fff;border:none;border-radius:12px;font-weight:600;font-size:1rem;cursor:pointer;transition:all .2s;box-shadow:0 4px 15px -3px rgba(247,37,133,.4)}
.chat-form-submit:hover{background:#d91a6f;transform:translateY(-1px)}
.chat-messages-container{flex:1;display:none;flex-direction:column;background:#1a1a2e;overflow:hidden}
.chat-messages-container.active{display:flex}
.chat-ticket-info{background:rgba(247,37,133,.1);border-bottom:1px solid rgba(247,37,133,.2);padding:.75rem 1rem;font-size:.85rem;color:#F72585;text-align:center;font-weight:500}
.chat-messages{flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.75rem;scroll-behavior:smooth;min-height:0}
.chat-message{display:flex;gap:10px;max-width:85%}
.chat-message-avatar{width:32px;height:32px;border-radius:10px;background:#0f0f1a;color:#9ca3af;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:600;flex-shrink:0}
.chat-message-content{flex:1;display:flex;flex-direction:column}
.chat-message-header{display:flex;align-items:baseline;gap:8px;margin-bottom:4px;padding:0 4px}
.chat-message-author{font-weight:600;font-size:.8rem;color:#e5e7eb}
.chat-message-time{font-size:.7rem;color:#6b7280}
.chat-message-text{background:#0f0f1a;padding:10px 14px;border-radius:4px 14px 14px 14px;font-size:.9rem;color:#e5e7eb;line-height:1.5;word-wrap:break-word}
.chat-message.user{flex-direction:row-reverse;align-self:flex-end}
.chat-message.user .chat-message-content{align-items:flex-end}
.chat-message.user .chat-message-header{flex-direction:row-reverse}
.chat-message.user .chat-message-text{background:#F72585;color:#fff;border-radius:14px 4px 14px 14px}
.chat-message.user .chat-message-avatar{display:none}
.chat-message.system{justify-content:center;max-width:100%;margin:.25rem 0}
.chat-message.system .chat-message-text{background:transparent;border:1px dashed rgba(247,37,133,.3);color:#9ca3af;text-align:center;font-size:.8rem;padding:.5rem 1rem;border-radius:8px}
.chat-input-container{padding:1rem;background:#1a1a2e;border-top:1px solid rgba(247,37,133,.1)}
.chat-input-form{display:flex;gap:8px;background:#0f0f1a;padding:6px;border-radius:14px;border:1px solid rgba(247,37,133,.2);transition:all .2s}
.chat-input-form:focus-within{border-color:#F72585;box-shadow:0 0 0 3px rgba(247,37,133,.1)}
.chat-input{flex:1;padding:10px 12px;background:transparent;border:none;font-size:.9rem;color:#fff;outline:none}
.chat-input::placeholder{color:#6b7280}
.chat-send-button{width:38px;height:38px;background:#F72585;color:#fff;border:none;border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;flex-shrink:0}
.chat-send-button:hover{background:#d91a6f;transform:scale(1.05)}
.chat-send-button:disabled{background:#374151;cursor:not-allowed;transform:none}
.chat-send-button svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2}
.chat-messages::-webkit-scrollbar{width:6px}
.chat-messages::-webkit-scrollbar-track{background:transparent}
.chat-messages::-webkit-scrollbar-thumb{background:rgba(247,37,133,.3);border-radius:10px}
@media(max-width:640px){.chat-widget{width:100%!important;height:100%!important;max-height:100%!important;bottom:0!important;right:0!important;left:0!important;top:0!important;border-radius:0!important;z-index:999999!important}.chat-widget-button{bottom:1rem!important;right:1rem!important;width:52px!important;height:52px!important}.chat-widget-button svg{width:24px;height:24px}.chat-header{padding:1rem;border-radius:0}.chat-header-avatar{width:36px;height:36px}.chat-user-form-container{padding:1.25rem}.chat-form-title{font-size:1.1rem}.chat-form-group input,.chat-form-group textarea{padding:.75rem;font-size:16px}.chat-messages{padding:.75rem}.chat-message{max-width:90%}.chat-message-text{padding:8px 12px;font-size:.85rem}.chat-input-container{padding:.75rem}.chat-input{padding:8px 10px;font-size:16px}.chat-send-button{width:36px;height:36px}.chat-close{width:36px;height:36px}}
</style>

<button class="chat-widget-button" id="chatWidgetToggle" aria-label="Open support chat">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
    <span class="chat-notification-badge" id="chatNotificationBadge">0</span>
</button>

<div class="chat-widget" id="chatWidgetContainer">
    <div class="chat-header">
        <div class="chat-header-info">
            <div class="chat-header-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <div class="chat-header-text">
                <h3>Support Team</h3>
                <div class="chat-header-status">
                    <span class="chat-status-dot" id="chatStatusDot"></span>
                    <span id="chatStatusText">Connecting...</span>
                </div>
            </div>
        </div>
        <button class="chat-close" id="chatWidgetClose"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
    </div>
    <div class="chat-user-form-container active" id="chatUserFormContainer">
        <h2 class="chat-form-title">Need Help? 👋</h2>
        <p class="chat-form-subtitle">Tell us about yourself and your question.</p>
        <form id="chatUserDetailsForm">
            <div class="chat-form-group"><label for="chatUserName">Your Name <span class="required">*</span></label><input type="text" id="chatUserName" placeholder="e.g. John Smith" required></div>
            <div class="chat-form-group"><label for="chatUserSubject">How can we help? <span class="required">*</span></label><textarea id="chatUserSubject" placeholder="Describe your question..." required></textarea></div>
            <button type="submit" class="chat-form-submit">Start Chat</button>
        </form>
    </div>
    <div class="chat-messages-container" id="chatMessagesContainer">
        <div class="chat-ticket-info">Ticket <span id="chatTicketNumber">#loading...</span></div>
        <div class="chat-messages" id="chatMessagesArea"></div>
        <div class="chat-input-container">
            <form class="chat-input-form" id="chatMessageForm">
                <input type="text" class="chat-input" id="chatMessageInput" placeholder="Type a message..." autocomplete="off" disabled>
                <button type="submit" class="chat-send-button" id="chatSendButton" disabled><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg></button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script>
(function(){
const SOCKET_URL='https://discordbot-ewmu.onrender.com',PROJECT_NAME='AMPMKP',SP='ampmkp_chat_';
const chatToggle=document.getElementById('chatWidgetToggle'),chatWidget=document.getElementById('chatWidgetContainer'),chatClose=document.getElementById('chatWidgetClose'),userFormContainer=document.getElementById('chatUserFormContainer'),chatMessagesContainer=document.getElementById('chatMessagesContainer'),userDetailsForm=document.getElementById('chatUserDetailsForm'),chatForm=document.getElementById('chatMessageForm'),chatInput=document.getElementById('chatMessageInput'),chatMessages=document.getElementById('chatMessagesArea'),sendButton=document.getElementById('chatSendButton'),statusDot=document.getElementById('chatStatusDot'),statusText=document.getElementById('chatStatusText'),ticketNumber=document.getElementById('chatTicketNumber'),notificationBadge=document.getElementById('chatNotificationBadge');
let socket,userId=localStorage.getItem(SP+'userId')||genId(),ticketId=localStorage.getItem(SP+'ticketId')||null,userDetails=JSON.parse(localStorage.getItem(SP+'userDetails')||'null'),unreadCount=0;
function genId(){const id='user_'+Math.random().toString(36).substr(2,9);localStorage.setItem(SP+'userId',id);return id}
function getHistory(){const h=localStorage.getItem(SP+'history');return h?JSON.parse(h):[]}
function saveHistory(h){localStorage.setItem(SP+'history',JSON.stringify(h))}
function appendHistory(m){const h=getHistory();h.push(m);if(h.length>100)h.shift();saveHistory(h)}
function clearHistory(){localStorage.removeItem(SP+'history')}
function loadHistory(){chatMessages.innerHTML='';getHistory().forEach(m=>{m.type==='system'?addSysMsg(m.text,false):addMsg(m.author||userDetails?.name||'User',m.text,m.type,m.timestamp,false)})}
function connectSocket(){if(socket&&socket.connected)return;socket=io(SOCKET_URL,{query:{userId,ticketId},reconnection:true,reconnectionAttempts:5});
socket.on('connect',()=>{updateStatus('online','Online');if(ticketId&&userDetails)showChat()});
socket.on('disconnect',()=>{updateStatus('offline','Offline');chatInput.disabled=true;sendButton.disabled=true});
socket.on('ticketCreated',d=>{ticketId=d.ticketId;localStorage.setItem(SP+'ticketId',ticketId);ticketNumber.textContent='#'+ticketId;chatInput.disabled=false;sendButton.disabled=false;const m1='Support ticket created for '+PROJECT_NAME+'!',m2='A support agent will be with you shortly.';addSysMsg(m1);addSysMsg(m2);appendHistory({type:'system',text:m1,timestamp:new Date().toISOString()});appendHistory({type:'system',text:m2,timestamp:new Date().toISOString()})});
socket.on('messageFromDiscord',d=>{addMsg(d.author,d.message,'discord',d.timestamp);appendHistory({type:'discord',author:d.author,text:d.message,timestamp:d.timestamp||new Date().toISOString()});if(!chatWidget.classList.contains('active')){unreadCount++;updateBadge()}});
socket.on('ticketClosed',d=>{addSysMsg('Ticket closed by '+d.closedBy+'.');chatInput.disabled=true;sendButton.disabled=true;setTimeout(()=>{localStorage.removeItem(SP+'ticketId');localStorage.removeItem(SP+'userDetails');clearHistory();ticketId=null;userDetails=null;chatMessages.innerHTML='';chatMessagesContainer.classList.remove('active');userFormContainer.classList.add('active');document.getElementById('chatUserName').value='';document.getElementById('chatUserSubject').value=''},5000)});
socket.on('systemMessage',m=>{addSysMsg(m);appendHistory({type:'system',text:m,timestamp:new Date().toISOString()})});
socket.on('error',e=>addSysMsg('Error: '+(e.message||'Connection issue')))}
function updateStatus(s,t){statusDot.className='chat-status-dot'+(s==='offline'?' offline':'');statusText.textContent=t}
function showChat(){userFormContainer.classList.remove('active');chatMessagesContainer.classList.add('active');ticketNumber.textContent=ticketId?'#'+ticketId:'#loading...';if(socket&&socket.connected){chatInput.disabled=false;sendButton.disabled=false}loadHistory()}
function updateBadge(){if(unreadCount>0){notificationBadge.textContent=unreadCount>9?'9+':unreadCount;notificationBadge.classList.add('active')}else{notificationBadge.classList.remove('active')}}
function escHtml(t){if(!t)return'';const d=document.createElement('div');d.textContent=t;return d.innerHTML}
function addMsg(author,text,type='discord',ts=null,save=true){const div=document.createElement('div');div.className='chat-message'+(type==='user'?' user':'');const time=ts?new Date(ts):new Date();const tStr=time.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});div.innerHTML='<div class="chat-message-avatar">'+(author?author.charAt(0).toUpperCase():'?')+'</div><div class="chat-message-content"><div class="chat-message-header"><span class="chat-message-author">'+escHtml(author)+'</span><span class="chat-message-time">'+tStr+'</span></div><div class="chat-message-text">'+escHtml(text)+'</div></div>';chatMessages.appendChild(div);chatMessages.scrollTop=chatMessages.scrollHeight}
function addSysMsg(text,save=true){const div=document.createElement('div');div.className='chat-message system';div.innerHTML='<div class="chat-message-content"><div class="chat-message-text">'+escHtml(text)+'</div></div>';chatMessages.appendChild(div);chatMessages.scrollTop=chatMessages.scrollHeight}
function sendMsg(){const msg=chatInput.value.trim();if(msg&&socket&&socket.connected&&ticketId){socket.emit('messageFromWebsite',{userId,ticketId,userName:userDetails.name,message:msg});addMsg(userDetails.name,msg,'user');appendHistory({type:'user',author:userDetails.name,text:msg,timestamp:new Date().toISOString()});chatInput.value='';chatInput.focus()}}
chatToggle.addEventListener('click',()=>{chatWidget.classList.add('active');unreadCount=0;updateBadge();if(userDetails&&ticketId){if(!socket||!socket.connected)connectSocket();chatInput.focus()}else{document.getElementById('chatUserName').focus()}});
chatClose.addEventListener('click',()=>chatWidget.classList.remove('active'));
userDetailsForm.addEventListener('submit',e=>{e.preventDefault();e.stopImmediatePropagation();const name=document.getElementById('chatUserName').value.trim(),subject=document.getElementById('chatUserSubject').value.trim();if(!name||!subject){alert('Please fill in all fields');return}if(!socket||!socket.connected)connectSocket();userDetails={name,subject:'['+PROJECT_NAME+'] '+subject};localStorage.setItem(SP+'userDetails',JSON.stringify(userDetails));socket.emit('createTicket',{userId,userDetails});showChat()});
chatForm.addEventListener('submit',e=>{e.preventDefault();e.stopImmediatePropagation();sendMsg();return false});
sendButton.addEventListener('click',e=>{e.preventDefault();e.stopImmediatePropagation();sendMsg();return false});
connectSocket()})();
</script>
