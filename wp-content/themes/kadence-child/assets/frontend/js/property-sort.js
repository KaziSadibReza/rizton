jQuery(document).ready(function ($) {
  // --- PRELOADER SETUP ---
  const preloaderHTML = `
    <div class="widget-ajax-preloader">
      <div class="logo-container">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="44" viewBox="0 0 40 44" fill="none" overflow="visible">
          <path class="logo-draw-path" d="M39.2665 13.0801V43.269H20.6012L13.1324 34.9501C11.698 33.3543 11.0745 31.1351 11.6544 29.0684C11.7721 28.6542 11.9291 28.2487 12.134 27.8607C13.2458 25.7373 15.7659 23.6183 21.386 23.6183L23.4221 36.1753C27.368 34.017 30.8735 31.3574 31.0043 25.8507C30.9781 20.9369 29.391 18.6392 26.4349 16.7644C24.438 15.4999 22.0836 14.9026 19.7117 14.9026H8.94245V43.269H0V13.0801L19.6333 0L39.2665 13.0801Z"/>
        </svg>
      </div>
    </div>`;

  // Inject preloader styles into the page head dynamically
  function addPreloaderStyles() {
    const styles = `
      .elementor-element-ff2254e {
        position: relative; /* Essential for overlay positioning */
        transition: min-height 0.3s ease-in-out;
      }
      .widget-ajax-preloader {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 10;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        border-radius: 8px;
      }
      .elementor-element-ff2254e.loading-active .widget-ajax-preloader {
        opacity: 1;
        visibility: visible;
      }
      .widget-ajax-preloader .logo-container { width: 100px; height: 110px; }
      .widget-ajax-preloader .logo-container svg { width: 100%; height: 100%; }
      .widget-ajax-preloader .logo-draw-path {
        fill: none; fill-opacity: 0; stroke: #3b82f6; stroke-width: 1;
        transform-origin: center;
        animation: draw 2s ease-in-out forwards, fillAndScale 0.5s ease-in-out 2s forwards;
      }
      @keyframes draw { to { stroke-dashoffset: 0; } }
      @keyframes fillAndScale { to { fill-opacity: 1; fill: #1E57DF; transform: scale(1.05); } }
    `;
    if (!$("#widget-preloader-styles").length) {
      $("head").append(
        '<style id="widget-preloader-styles">' + styles + "</style>"
      );
    }
  }

  // Show/Hide Functions for the Preloader
  function showWidgetPreloader() {
    const $widget = $(".elementor-element-ff2254e");
    if ($widget.length) {
      const currentHeight = $widget.find(".elementor-loop-container").height();
      $widget
        .find(".elementor-loop-container")
        .css("min-height", currentHeight);
      $widget.addClass("loading-active").prepend(preloaderHTML);
      const logoPath = $widget.find(".logo-draw-path")[0];
      if (logoPath) {
        const length = logoPath.getTotalLength();
        logoPath.style.strokeDasharray = length;
        logoPath.style.strokeDashoffset = length;
      }
    }
  }

  function hideWidgetPreloader() {
    const $widget = $(".elementor-element-ff2254e");
    if ($widget.length) {
      $widget.find(".widget-ajax-preloader").fadeOut(300, function () {
        $(this).remove();
        $widget.removeClass("loading-active");
        $widget.find(".elementor-loop-container").css("min-height", "");
      });
    }
  }

  initializeCustomSelect();
  addPreloaderStyles();

  function initializeCustomSelect() {
    $(".property-sort-widget select").each(function () {
      const $select = $(this);
      if (
        $select
          .closest(".property-sort-widget")
          .find(".property-sort-select-wrapper").length
      )
        return;

      const $wrapper = $('<div class="property-sort-select-wrapper"></div>');
      const $customSelect = $(
        '<div class="property-sort-select-custom"></div>'
      );
      const currentValue = $select.val();
      const currentText = currentValue
        ? $select.find("option:selected").text()
        : $select.find("option:first").text();
      const $trigger = $(
        `<div class="property-sort-select-trigger ${
          currentValue ? "has-value" : ""
        }"><span class="property-sort-select-text">${currentText}</span></div>`
      );
      const $optionsContainer = $(
        '<div class="property-sort-select-options"></div>'
      );

      $select.find("option").each(function () {
        const $option = $(this);
        const value = $option.val();
        const text = $option.text();
        const $customOption = $(
          `<div class="property-sort-select-option" data-value="${value}">${text}</div>`
        );
        $optionsContainer.append($customOption);
      });

      $customSelect.append($trigger).append($optionsContainer);
      $wrapper.append($customSelect);
      $select.hide().after($wrapper);
      setupCustomSelectEvents($customSelect, $select);
    });
  }

  function setupCustomSelectEvents($customSelect, $originalSelect) {
    const $trigger = $customSelect.find(".property-sort-select-trigger");
    const $options = $customSelect.find(".property-sort-select-option");
    const $text = $customSelect.find(".property-sort-select-text");

    $trigger.on("click", function (e) {
      e.stopPropagation();
      $(".property-sort-select-custom.active")
        .not($customSelect)
        .removeClass("active");
      $customSelect.toggleClass("active");
    });

    $options.on("click", function (e) {
      e.stopPropagation();
      const $option = $(this);
      const value = $option.data("value");
      const text = $option.text();

      $options.removeClass("selected");
      $option.addClass("selected");
      $text.text(text);
      $trigger.toggleClass("has-value", !!value);

      $originalSelect.val(value).trigger("change");
      handleAjaxSort(value);
      $customSelect.removeClass("active");
    });

    $(document).on("click", () => $customSelect.removeClass("active"));
    $(document).on("keydown", (e) => {
      if (e.key === "Escape") $customSelect.removeClass("active");
    });
  }

  // --- REVISED AJAX HANDLING FUNCTION (NO PHP CHANGE REQUIRED) ---
  function handleAjaxSort(sortValue) {
    showWidgetPreloader();

    // This AJAX call validates the request, but we won't use its response to update content.
    // It's kept here in case your PHP has other functions tied to this action hook.
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "ajax_property_sort",
        sort_by: sortValue,
        nonce: getAjaxNonce(),
      },
      dataType: "json",
      success: function (initialResponse) {
        if (initialResponse.success) {
          // The request was valid. Now we fetch the new page content via a partial reload.
          const newUrl = updateUrlWithoutReload(sortValue); // Update URL and get the new one

          // Use $.get to fetch the full HTML of the page with the new sort parameter
          $.get(newUrl)
            .done(function (data) {
              // Find the new content within the fetched HTML
              const $newContent = $(data);
              const $newLoopContainer = $newContent.find(
                ".elementor-element-ff2254e .elementor-loop-container"
              );

              if ($newLoopContainer.length) {
                // Replace the old content with the new content
                findPropertiesContainer().html($newLoopContainer.html());
              } else {
                // If the container isn't found, reload the whole page as a fallback
                window.location.href = newUrl;
                return;
              }
              hideWidgetPreloader(); // Hide the preloader after content is successfully replaced
            })
            .fail(function () {
              // If the fetch fails, just reload the page to show the sorted content
              window.location.href = newUrl;
            });
        } else {
          // The initial AJAX validation from your PHP failed
          console.error("AJAX validation failed.", initialResponse);
          showErrorMessage("Sorting request was not valid.");
          hideWidgetPreloader();
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX request failed:", { status, error });
        showErrorMessage("An error occurred while trying to sort.");
        hideWidgetPreloader();
      },
    });
  }

  // Utility functions
  function findPropertiesContainer() {
    return $(".elementor-element-ff2254e .elementor-loop-container");
  }

  // This function now returns the new URL so we can use it for the $.get request
  function updateUrlWithoutReload(sortValue) {
    const urlParams = new URLSearchParams(window.location.search);
    if (sortValue) {
      urlParams.set("sort_by", sortValue);
    } else {
      urlParams.delete("sort_by");
    }
    urlParams.delete("paged"); // Always reset to first page on sort
    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
    history.pushState({ path: newUrl }, "", newUrl);
    return newUrl; // Return the newly constructed URL
  }

  function showErrorMessage(message) {
    alert(message);
  }
  function getAjaxUrl() {
    return window.ajax_object?.ajax_url || "/wp-admin/admin-ajax.php";
  }
  function getAjaxNonce() {
    return window.ajax_object?.nonce || "default_nonce";
  }
});
