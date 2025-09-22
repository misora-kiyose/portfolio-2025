// slider.js
(() => {
  const DURATION = 1500;        // アニメ時間(ms)
  const INTERVAL = 4000;        // 自動送り間隔(ms)
  const REAL_COUNT = 3;         // 本物の枚数（ダミー除く）

  const container = document.querySelector(".slider-container");
  const track = document.getElementById("slider-track");
  if (!container || !track) return;

  const images = Array.from(track.querySelectorAll("img"));
  let index = 1;     // [dummy-last][1][2][3][dummy-first] の2番目から開始
  let timer = null;
  let resizeId = null;

  // 画像の読み込み完了を待つ（高さのブレ防止）
  const readyImages = () =>
    Promise.all(images.map(img => {
      if ('decode' in img) return img.decode().catch(()=>{});
      return img.complete ? Promise.resolve() :
             new Promise(res => img.addEventListener('load', res, { once:true }));
    }));

  const slideW = () => container.offsetWidth;

  const setHeight = () => {
    // 現在スライド or 1枚目の高さを参照。fallback は 16:9
    const h = (images[index] && images[index].clientHeight) ||
              (images[1] && images[1].clientHeight) ||
              Math.round(slideW() * 9 / 16);
    container.style.height = h + "px";
  };

  const applyTransform = (animate = true) => {
    track.style.transition = animate ? `transform ${DURATION}ms ease-in-out` : "none";
    // translate3d でGPU合成 → カクつき減
    track.style.transform = `translate3d(-${slideW() * index}px,0,0)`;
  };

  const move = () => {
    index++;
    applyTransform(true);

    // ダミー終端 → 本物1枚目へ瞬間戻し
    if (index === REAL_COUNT + 1) {
      setTimeout(() => {
        index = 1;
        applyTransform(false);
        requestAnimationFrame(setHeight);
      }, DURATION);
    } else {
      requestAnimationFrame(setHeight);
    }
  };

  const start = () => { stop(); timer = setInterval(move, INTERVAL); };
  const stop  = () => { if (timer) { clearInterval(timer); timer = null; } };

  const onResize = () => {
    clearTimeout(resizeId);
    resizeId = setTimeout(() => {
      applyTransform(false); // 横幅に合わせて位置を再計算
      setHeight();           // 高さも再計算
    }, 120);                 // デバウンス
  };

  // タブ非表示時は停止→復帰で再開（ズレ防止）
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) stop(); else start();
  });

  // 初期化
  (async () => {
    await readyImages();
    applyTransform(false);
    setHeight();
    window.addEventListener("resize", onResize);
    window.addEventListener("orientationchange", onResize);
    start();
  })();
})();
