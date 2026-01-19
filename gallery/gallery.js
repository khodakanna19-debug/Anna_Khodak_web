(function(){
  const thumbs = Array.from(document.querySelectorAll('.thumb-btn'));
  const lightbox = document.querySelector('.lightbox');
  const lbImage = document.querySelector('.lb-image');
  const lbCaption = document.querySelector('.lb-caption');
  const btnClose = document.querySelector('.lb-close');
  const btnPrev = document.querySelector('.lb-prev');
  const btnNext = document.querySelector('.lb-next');
  if(!thumbs.length || !lightbox) return;

  let current = 0;
  const images = thumbs.map(btn=>{
    const img = btn.querySelector('img');
    return {
      full: img.dataset.full || img.src,
      alt: img.alt || '',
      caption: btn.querySelector('figcaption')?.textContent || ''
    };
  });

  function openAt(index){
    current=(index+images.length)%images.length;
    const item=images[current];
    lbImage.src=item.full;
    lbImage.alt=item.alt;
    lbCaption.textContent=item.caption;
    lightbox.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
  }

  function close(){
    lightbox.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
  }

  thumbs.forEach((btn,i)=>btn.addEventListener('click',e=>{e.preventDefault(); openAt(i);}));
  btnClose.addEventListener('click',close);
  btnPrev.addEventListener('click',()=>openAt(current-1));
  btnNext.addEventListener('click',()=>openAt(current+1));
  lightbox.addEventListener('click',e=>{if(e.target===lightbox) close();});

  document.addEventListener('keydown',e=>{
    if(lightbox.getAttribute('aria-hidden')==='true') return;
    if(e.key==='Escape') close();
    if(e.key==='ArrowLeft') openAt(current-1);
    if(e.key==='ArrowRight') openAt(current+1);
  });

  document.getElementById('year').textContent=new Date().getFullYear();
})();
