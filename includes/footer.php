<?php
// File: includes/footer.php

// Determine if we should show chatbot (not on login page)
$current_page = basename($_SERVER['PHP_SELF']);
$show_chatbot = ($current_page !== 'login.php');
?>
    </div> <!-- .container (opened in header) -->

    <!-- Modern Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="footer-content">
                <div class="footer-copyright">
                    <i class="fas fa-flask me-3"></i>
                    <span>&copy; <?= date('Y') ?> Chemical Laboratory Inventory System. All rights reserved.</span>
                </div>

                <div class="footer-social">
                    <a href="https://www.linkedin.com/in/manjunath-ka/" class="social-link" data-bs-toggle="tooltip" title="Follow on LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>

                    <a href="mailto:kalurimanjunath@gmail.com" class="social-link" data-bs-toggle="tooltip" title="Contact via Email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>


<?php if ($show_chatbot): ?>

<!-- ================= CHATBOT WIDGET ================= -->

<style>

.chatbot-toggle{
position:fixed;
bottom:25px;
right:25px;
width:60px;
height:60px;
border-radius:50%;
background:#007bff;
color:white;
border:none;
cursor:pointer;
box-shadow:0 4px 12px rgba(0,0,0,0.25);
z-index:2000;
display:flex;
align-items:center;
justify-content:center;
font-size:26px;
transition:transform .3s;
}

.chatbot-toggle:hover{
transform:scale(1.1);
}

.chatbot-panel{
position:fixed;
bottom:100px;
right:25px;
width:360px;
height:520px;
background:white;
border-radius:15px;
box-shadow:0 8px 40px rgba(0,0,0,.25);
z-index:2000;
display:none;
flex-direction:column;
overflow:hidden;
border:1px solid #ddd;
}

.chatbot-header{
background:#007bff;
color:white;
padding:14px;
font-weight:600;
display:flex;
justify-content:space-between;
align-items:center;
}

.chatbot-header button{
background:none;
border:none;
color:white;
font-size:18px;
cursor:pointer;
}

.chatbot-messages{
flex:1;
padding:15px;
overflow-y:auto;
background:#f8f9fa;
display:flex;
flex-direction:column;
}

.message{
max-width:80%;
margin-bottom:10px;
padding:8px 12px;
border-radius:18px;
word-wrap:break-word;
}

.user-message{
align-self:flex-end;
background:#007bff;
color:white;
border-bottom-right-radius:4px;
}

.bot-message{
align-self:flex-start;
background:#e9ecef;
border-bottom-left-radius:4px;
}

.chatbot-input-area{
display:flex;
padding:10px;
border-top:1px solid #ddd;
background:white;
}

.chatbot-input-area input{
flex:1;
padding:8px 12px;
border:1px solid #ccc;
border-radius:20px;
outline:none;
margin-right:8px;
}

.chatbot-input-area button{
background:#007bff;
color:white;
border:none;
border-radius:20px;
padding:8px 16px;
cursor:pointer;
}

.typing-indicator{
display:flex;
padding:8px 12px;
background:#e9ecef;
border-radius:18px;
align-self:flex-start;
margin-bottom:10px;
}

.typing-indicator span{
height:8px;
width:8px;
background:#666;
border-radius:50%;
display:inline-block;
margin:0 2px;
animation:typing 1.4s infinite;
}

.typing-indicator span:nth-child(2){animation-delay:.2s;}
.typing-indicator span:nth-child(3){animation-delay:.4s;}

@keyframes typing{
0%,60%,100%{opacity:.3;transform:translateY(0);}
30%{opacity:1;transform:translateY(-5px);}
}

</style>


<button class="chatbot-toggle" onclick="toggleChat()">
<i class="fas fa-comment"></i>
</button>

<div class="chatbot-panel" id="chatbotPanel">

<div class="chatbot-header">
<span>Lab Assistant</span>
<button onclick="toggleChat()">&times;</button>
</div>

<div class="chatbot-messages" id="chatMessages">
<div class="message bot-message">
Hello! I'm your lab assistant. Ask me anything about chemicals, inventory, or lab procedures.
</div>
</div>

<div class="chatbot-input-area">
<input type="text" id="chatInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter') sendMessage()">
<button id="sendBtn" onclick="sendMessage()">Send</button>
</div>

</div>


<script>

let messages = [
{
role:'assistant',
content:"Hello! I'm your lab assistant. Ask me anything about chemicals, inventory, or lab procedures."
}
];

function toggleChat(){
const panel=document.getElementById('chatbotPanel');
panel.style.display=(panel.style.display==='flex')?'none':'flex';
}

function addMessage(role,text){

const box=document.getElementById('chatMessages');

const msg=document.createElement('div');
msg.className=`message ${role==='user'?'user-message':'bot-message'}`;
msg.textContent=text;

box.appendChild(msg);

box.scrollTop=box.scrollHeight;

messages.push({role:role,content:text});
}

function showTyping(){

const box=document.getElementById('chatMessages');

const typing=document.createElement('div');
typing.className='typing-indicator';
typing.id='typingIndicator';
typing.innerHTML='<span></span><span></span><span></span>';

box.appendChild(typing);
box.scrollTop=box.scrollHeight;
}

function hideTyping(){
const t=document.getElementById('typingIndicator');
if(t)t.remove();
}

async function sendMessage(){

const input=document.getElementById('chatInput');
const btn=document.getElementById('sendBtn');

const text=input.value.trim();
if(!text)return;

addMessage('user',text);

input.value='';
btn.disabled=true;
input.disabled=true;

showTyping();

try{

const response=await fetch('/chemical_inventory/chatbot/chat_api.php',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify({messages})
});

hideTyping();

const data=await response.json();

if(data.reply){
addMessage('assistant',data.reply);
}else{
addMessage('assistant','AI error: '+(data.error || 'Unknown error'));
}

}catch(e){

hideTyping();
addMessage('assistant','Server unavailable.');

}

btn.disabled=false;
input.disabled=false;
input.focus();
}

</script>

<?php endif; ?>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
var tooltipTriggerList=[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
tooltipTriggerList.map(function(el){return new bootstrap.Tooltip(el)})
</script>


<style>
.footer-modern{
background:linear-gradient(45deg,#1e1e2f,#2a2a40);
border-top:1px solid rgba(255,255,255,.1);
padding:1.5rem 0;
margin-top:3rem;
color:rgba(255,255,255,.8);
font-family:'Poppins',sans-serif;
}

.footer-content{
display:flex;
justify-content:space-between;
align-items:center;
flex-wrap:wrap;
gap:1rem;
}

.footer-social{
display:flex;
gap:1rem;
}

.social-link{
color:rgba(255,255,255,.7);
font-size:1.2rem;
transition:.3s;
width:36px;
height:36px;
border-radius:50%;
background:rgba(255,255,255,.1);
display:flex;
align-items:center;
justify-content:center;
text-decoration:none;
}

.social-link:hover{
color:#fff;
background:linear-gradient(45deg,#667eea,#764ba2);
transform:translateY(-3px);
}
</style>

</body>
</html>