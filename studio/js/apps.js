class AppGridManager {
  constructor() {
    this.grid = document.getElementById('gf-app-grid');
    this.showMoreContainer = document.getElementById('gf-show-more-container');
    this.showMore = document.getElementById('gf-show-more');
    this.expanded = false;
    this.maxRows = 3;
    this.maxRetries = 5;
    this.retryDelay = 1000;
    this.populateGrid = this.populateGrid.bind(this);
    this.updateVisibility = this.updateVisibility.bind(this);
    this.toggleExpand = this.toggleExpand.bind(this);
    this.handleResize = this.debounce(this.updateVisibility, 200);
    this.setupEventListeners();
    this.populateGridWithRetry(0);
  }
  debounce(fn, wait) {
    let timeout;
    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => fn.apply(this, args), wait);
    };
  }
  setupEventListeners() {
    document.addEventListener('apps-updated', this.populateGrid);
    window.addEventListener('resize', this.handleResize);
  }
  populateGrid() {
    const menuAppsContainer = document.getElementById('sys-menu-apps');
    this.grid.innerHTML = '';
    if (!menuAppsContainer) {
      console.warn('Apps container (#sys-menu-apps) not found');
      return;
    }
    const appLinks = menuAppsContainer.querySelectorAll('.bx-popup-content a');
    if (appLinks.length === 0) {
      console.warn('No app links found in #sys-menu-apps');
      return;
    }
    const appSlides = [];
    const imagePromises = [];
    appLinks.forEach(appLink => {
      const appSlide = document.createElement('div');
      appSlide.classList.add('gf-app-slide');
      const appImage = appLink.querySelector('.sys-icon-image');
      const appName = appLink.querySelector('.text-sm');
      if (!appImage || !appName) {
        console.warn('Skipping slide: missing image or name', appLink);
        return;
      }
      appSlide.innerHTML = `
        <div class="gf-logo-box">
          <img src="${appImage.src}" alt="${appName.textContent}">
        </div>
        <span>${appName.textContent}</span>
      `;
      appSlide.dataset.href = appLink.href;
      const img = appSlide.querySelector('img');
      if (img) {
        const promise = new Promise((resolve, reject) => {
          img.onload = resolve;
          img.onerror = () => reject(new Error(`Failed to load image: ${appImage.src}`));
        });
        imagePromises.push(promise);
      }
      appSlides.push(appSlide);
    });
    const addSlide = document.createElement('div');
    addSlide.classList.add('gf-app-slide', 'gf-add-app');
    addSlide.innerHTML = `
      <div class="gf-logo-box">+</div>
      <span>Add App</span>
    `;
    addSlide.dataset.href = 'https://www.gfunnel.com/add-app';
    appSlides.unshift(addSlide);
    if (appSlides.length === 0) {
      console.warn('No valid slides created');
      return;
    }
    Promise.all(imagePromises)
      .then(() => {
        appSlides.forEach(slide => this.grid.appendChild(slide));
        Array.from(this.grid.children).forEach(slide => {
          slide.addEventListener('click', (e) => {
            if (slice.dataset.href) {
              window.open(slide.dataset.href, '_blank');
            }
          });
        });
        this.updateVisibility();
      })
      .catch(error => {
        console.error('Error loading images:', error);
        appSlides.forEach(slide => this.grid.appendChild(slide));
        Array.from(this.grid.children).forEach(slide => {
          slide.addEventListener('click', (e) => {
            if (slide.dataset.href) {
              window.open(slide.dataset.href, '_blank');
            }
          });
        });
        this.updateVisibility();
      });
  }
  populateGridWithRetry(retryCount) {
    const menuAppsContainer = document.getElementById('sys-menu-apps');
    if (menuAppsContainer && menuAppsContainer.querySelectorAll('.bx-popup-content a').length > 0) {
      this.populateGrid();
    } else if (retryCount < this.maxRetries) {
      console.warn(`Retry ${retryCount + 1}: Apps container not ready, retrying in ${this.retryDelay}ms`);
      setTimeout(() => this.populateGridWithRetry(retryCount + 1), this.retryDelay);
    } else {
      console.error('Max retries reached: Apps container not populated');
    }
  }
  updateVisibility() {
    this.showMore.removeEventListener('click', this.toggleExpand);
    this.grid.querySelectorAll('.gf-hidden').forEach(el => el.classList.remove('gf-hidden'));
    this.showMoreContainer.style.display = 'none';
    const slides = Array.from(this.grid.children);
    if (slides.length === 0) return;
    const firstTop = slides[0].offsetTop;
    let colCount = 0;
    while (colCount < slides.length && slides[colCount].offsetTop === firstTop) {
      colCount++;
    }
    const maxVisible = colCount * this.maxRows;
    if (slides.length <= maxVisible) {
      return;
    }
    this.showMoreContainer.style.display = 'block';
    this.showMore.textContent = this.expanded ? 'Show Less' : 'Show More';
    if (!this.expanded) {
      slides.slice(maxVisible).forEach(slide => slide.classList.add('gf-hidden'));
    }
    this.showMore.addEventListener('click', this.toggleExpand);
  }
  toggleExpand() {
    this.expanded = !this.expanded;
    this.updateVisibility();
  }
}
document.addEventListener('DOMContentLoaded', () => {
  new AppGridManager();
});
function setupAppsMutationObserver() {
  const menuAppsContainer = document.getElementById('sys-menu-apps');
  if (!menuAppsContainer) {
    console.warn('MutationObserver: #sys-menu-apps not found, retrying...');
    setTimeout(setupAppsMutationObserver, 1000);
    return;
  }
  const observer = new MutationObserver(() => {
    document.dispatchEvent(new CustomEvent('apps-updated'));
  });
  observer.observe(menuAppsContainer, {
    childList: true,
    subtree: true,
    characterData: true
  });
}
document.addEventListener('DOMContentLoaded', setupAppsMutationObserver);
document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('touchstart', (event) => {
    if (event.touches.length > 1) {
      event.preventDefault();
    }
  }, { passive: false });
  let lastTouchEnd = 0;
  document.addEventListener('touchend', (event) => {
    const now = new Date().getTime();
    if (now - lastTouchEnd <= 300) {
      event.preventDefault();
    }
    lastTouchEnd = now;
  }, { passive: false });
});