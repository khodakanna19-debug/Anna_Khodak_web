const playfield = document.getElementById('game-area');
let basket = document.getElementById('basket');
const startBtn = document.getElementById('start-btn');
const scoreEl = document.getElementById('score');
const missedEl = document.getElementById('missed');

let items = [];
let running = false;
let score = 0;
let missed = 0;
let basketX = 0, basketW = 0, basketH = 0, fieldRect;

function recalcSizes(){
    fieldRect = playfield.getBoundingClientRect();
    const bRect = basket.getBoundingClientRect();
    basketW = bRect.width;
    basketH = bRect.height;
    basketX = basket.style.left ? parseFloat(basket.style.left) : (fieldRect.width - basketW)/2;
    basket.style.left = basketX + 'px';
}

window.addEventListener('resize', recalcSizes);
window.addEventListener('load', recalcSizes);

// Керування кошиком
function setupBasketDrag(){
    let dragging=false, pointerId=null;
    basket.addEventListener('pointerdown', e=>{
        e.preventDefault();
        dragging=true; pointerId=e.pointerId;
        basket.setPointerCapture && basket.setPointerCapture(pointerId);
        recalcSizes();
    });
    basket.addEventListener('pointermove', e=>{
        if(!dragging || e.pointerId!==pointerId) return;
        basketX = Math.min(Math.max(0, e.clientX - fieldRect.left - basketW/2), fieldRect.width-basketW);
        basket.style.left = basketX + 'px';
    });
    basket.addEventListener('pointerup', e=>{
        if(e.pointerId===pointerId){ dragging=false; basket.releasePointerCapture && basket.releasePointerCapture(pointerId); pointerId=null; }
    });
    basket.addEventListener('pointercancel', ()=>{ dragging=false; pointerId=null; });
}
setupBasketDrag();

playfield.addEventListener('pointerdown', e=>{
    if(e.target === basket || basket.contains(e.target)) return;
    basketX = Math.min(Math.max(0, e.clientX - fieldRect.left - basketW/2), fieldRect.width-basketW);
    basket.style.left = basketX + 'px';
});

// Спавн котиків
function spawnItem(){
    const el = document.createElement('div');
    el.className='cat';
    el.style.left=Math.random()*(fieldRect.width-50)+'px';
    el.style.top='-50px';
    const isGolden = Math.random()<0.1;
    if(isGolden) el.classList.add('gold-cat');
    playfield.appendChild(el);
    items.push({el, y:-50, speed:150+Math.random()*50, isGolden});
}

// Анімація
function loop(){
    if(running){
        items.forEach((it,i)=>{
            it.y += it.speed/60;
            it.el.style.top = it.y+'px';
            const itRect = it.el.getBoundingClientRect();
            const bRect = basket.getBoundingClientRect();
            if(!(itRect.right < bRect.left || itRect.left > bRect.right || itRect.bottom < bRect.top || itRect.top > bRect.bottom)){
                score += it.isGolden?40:10;
                scoreEl.textContent=score;
                if(it.isGolden){ basket.classList.add('gold-glow'); setTimeout(()=>basket.classList.remove('gold-glow'),400); }
                else { basket.classList.add('glow'); setTimeout(()=>basket.classList.remove('glow'),300); }
                it.el.remove(); items.splice(i,1);
            } else if(it.y>fieldRect.height){
                missed++; missedEl.textContent=missed;
                it.el.remove(); items.splice(i,1);
                if(missed>=3) endGame();
            }
        });
    }
    requestAnimationFrame(loop);
}
requestAnimationFrame(loop);

// Почати гру
function startGame(){
    running=true;
    score=0; missed=0;
    scoreEl.textContent=score; missedEl.textContent=missed;

    // Створюємо кошик заново
    playfield.innerHTML=`<div id="basket"><img src="basket.gif" alt="кошик" class="basket-img"></div>`;
    basket=document.getElementById('basket');
    recalcSizes();
    setupBasketDrag();
    items=[];
    if(window.spawnInterval) clearInterval(window.spawnInterval);
    window.spawnInterval=setInterval(spawnItem,1200);
}

// Кінець гри
function endGame(){
    running=false;
    clearInterval(window.spawnInterval);
    items.forEach(it=>it.el.remove());
    items=[];
    playfield.innerHTML=`
        <img src="end.gif" alt="The End Cat" class="end-gif">
        <div id="final-score">Ваш рахунок: ${score}</div>
        <button id="restart-btn">Грати ще раз</button>
    `;
    document.getElementById('restart-btn').addEventListener('click', startGame);
}

startBtn.addEventListener('click', startGame);
