/**
 * Property Gallery Slider with SwiperJS
 */
if (typeof PropertyGallerySlider === "undefined") {
  window.PropertyGallerySlider = class PropertyGallerySlider {
    constructor(container) {
      this.container = container;
      this.mainSwiper = null;
      this.thumbsSwiper = null;
      this.totalSlides = 0;

      this.init();
    }

    init() {
      this.totalSlides = this.container.querySelectorAll(
        ".main-slider .swiper-slide"
      ).length;

      if (this.totalSlides <= 0) {
        console.log("No slides found");
        return;
      }

      this.initThumbnailSlider();
      this.initMainSlider();
      this.bindCustomNavigation();
      this.createCustomPagination();
    }

    initThumbnailSlider() {
      const thumbsContainer = this.container.querySelector(".thumbnail-slider");
      if (!thumbsContainer) return;

      this.thumbsSwiper = new Swiper(thumbsContainer, {
        spaceBetween: 10,
        slidesPerView: "auto",
        freeMode: true,
        watchSlidesProgress: true,
        breakpoints: {
          320: {
            slidesPerView: 4,
            spaceBetween: 5,
          },
          480: {
            slidesPerView: 5,
            spaceBetween: 8,
          },
          768: {
            slidesPerView: 6,
            spaceBetween: 10,
          },
          1024: {
            slidesPerView: 8,
            spaceBetween: 10,
          },
        },
      });
    }

    initMainSlider() {
      const mainContainer = this.container.querySelector(".main-slider");
      if (!mainContainer) return;

      this.mainSwiper = new Swiper(mainContainer, {
        spaceBetween: 0,
        effect: "slide",
        speed: 400,
        loop: this.totalSlides > 1,
        thumbs: {
          swiper: this.thumbsSwiper,
        },
        on: {
          slideChange: () => {
            this.updateCustomPagination();
          },
        },
      });
    }

    bindCustomNavigation() {
      const prevBtn = this.container.querySelector(".left-icon");
      const nextBtn = this.container.querySelector(".right-icon");

      if (prevBtn) {
        prevBtn.addEventListener("click", () => {
          if (this.mainSwiper) {
            this.mainSwiper.slidePrev();
          }
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener("click", () => {
          if (this.mainSwiper) {
            this.mainSwiper.slideNext();
          }
        });
      }

      // Update button states
      if (this.mainSwiper) {
        this.mainSwiper.on("slideChange", () => {
          this.updateNavigationButtons();
        });

        // Initial state
        this.updateNavigationButtons();
      }
    }

    updateNavigationButtons() {
      const prevBtn = this.container.querySelector(".left-icon");
      const nextBtn = this.container.querySelector(".right-icon");

      if (!this.mainSwiper) return;

      // Handle loop mode
      if (this.mainSwiper.params.loop) {
        if (prevBtn) prevBtn.classList.remove("swiper-button-disabled");
        if (nextBtn) nextBtn.classList.remove("swiper-button-disabled");
      } else {
        // Handle non-loop mode
        if (prevBtn) {
          if (this.mainSwiper.isBeginning) {
            prevBtn.classList.add("swiper-button-disabled");
          } else {
            prevBtn.classList.remove("swiper-button-disabled");
          }
        }

        if (nextBtn) {
          if (this.mainSwiper.isEnd) {
            nextBtn.classList.add("swiper-button-disabled");
          } else {
            nextBtn.classList.remove("swiper-button-disabled");
          }
        }
      }
    }

    createCustomPagination() {
      const paginationContainer =
        this.container.querySelector(".dash-pagination");
      if (!paginationContainer || this.totalSlides <= 1) return;

      // Clear existing pagination
      paginationContainer.innerHTML = "";

      // Create dashes for each slide
      for (let i = 0; i < this.totalSlides; i++) {
        const dash = document.createElement("span");
        dash.className = "dash";
        if (i === 0) dash.classList.add("active");

        dash.addEventListener("click", () => {
          if (this.mainSwiper) {
            this.mainSwiper.slideTo(i);
          }
        });

        paginationContainer.appendChild(dash);
      }
    }

    updateCustomPagination() {
      const dashes = this.container.querySelectorAll(".dash");
      if (!this.mainSwiper || dashes.length === 0) return;

      const activeIndex =
        this.mainSwiper.realIndex || this.mainSwiper.activeIndex;

      dashes.forEach((dash, index) => {
        if (index === activeIndex) {
          dash.classList.add("active");
        } else {
          dash.classList.remove("active");
        }
      });
    }
  };
} // Close the PropertyGallerySlider class declaration check

// Initialize sliders when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Only run if there are gallery sliders on the page
  const sliders = document.querySelectorAll(".property-gallery-slider");
  if (sliders.length === 0) {
    return; // No galleries found, don't load Swiper or show errors
  }

  // Check if Swiper is loaded
  if (typeof Swiper === "undefined") {
    console.error(
      "Swiper library is not loaded. Please include Swiper CSS and JS files."
    );
    return;
  }

  sliders.forEach((slider) => {
    new PropertyGallerySlider(slider);
  });
});

// Function to initialize sliders for dynamically loaded content
window.initPropertySliders = function () {
  const sliders = document.querySelectorAll(
    ".property-gallery-slider:not([data-initialized])"
  );

  if (sliders.length === 0) {
    return; // No new galleries found
  }

  if (typeof Swiper === "undefined") {
    console.error("Swiper library is not loaded.");
    return;
  }

  sliders.forEach((slider) => {
    slider.setAttribute("data-initialized", "true");
    new PropertyGallerySlider(slider);
  });
};
