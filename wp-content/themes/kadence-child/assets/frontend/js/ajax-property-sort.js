jQuery(document).ready(function ($) {
  "use strict";

  // Initialize all sort widgets on the page
  $(".ajax-property-sort-widget").each(function () {
    initializeAjaxSortWidget($(this));
  });

  function initializeAjaxSortWidget($widget) {
    const $sortDropdown = $widget.find(".sort-dropdown");
    const $loadingIndicator = $widget.find(".sort-loading-indicator");
    
    const targetContainer = $widget.data("target") || ".elementor-loop-container";
    const queryId = $widget.data("query-id") || "6969";

    // Find the target container for properties
    let $propertiesContainer = $(targetContainer);
    
    // Fallback selectors if target not found
    if (!$propertiesContainer.length) {
      const fallbackSelectors = [
        '.elementor-loop-container',
        '.elementor-posts-container', 
        '.elementor-posts',
        '.elementor-widget-loop-grid .elementor-posts-container',
        '[data-query-id="' + queryId + '"]',
        '.e-loop-container'
      ];
      
      for (let selector of fallbackSelectors) {
        $propertiesContainer = $(selector);
        if ($propertiesContainer.length) {
          console.log("AJAX Sort: Using container:", selector);
          break;
        }
      }
    }

    if (!$propertiesContainer.length) {
      console.warn("AJAX Property Sort: No target container found");
      return;
    }

    // Handle sort dropdown change
    $sortDropdown.on("change", function () {
      const selectedSort = $(this).val();
      if (selectedSort) {
        performAjaxSort(selectedSort);
      }
    });

    function performAjaxSort(sortType) {
      showLoadingState(true);

      // Get current URL parameters to maintain existing filters
      const urlFilters = getCurrentUrlFilters();

      // Prepare AJAX data
      const ajaxData = {
        action: "property_sort_ajax",
        nonce: ajaxPropertySort.nonce,
        sort_type: sortType,
        query_id: queryId,
        url_filters: JSON.stringify(urlFilters),
      };

      // Perform AJAX request
      $.ajax({
        url: ajaxPropertySort.ajax_url,
        type: "POST",
        data: ajaxData,
        dataType: "json",
        timeout: 10000,
        
        success: function (response) {
          if (response.success && response.data) {
            // Replace content
            $propertiesContainer.html(response.data.html);
            
            // Update URL for bookmarking
            updateUrlWithSort(sortType);
            
            // Trigger custom event
            $(document).trigger("ajaxPropertySortComplete", [response.data, sortType]);
            
            // Scroll to results
            $("html, body").animate({
              scrollTop: $propertiesContainer.offset().top - 100
            }, 500);
            
          } else {
            showErrorMessage("Failed to sort properties. Please try again.");
          }
        },
        
        error: function (xhr, status, error) {
          console.error("AJAX Sort Error:", status, error);
          showErrorMessage(ajaxPropertySort.error_text);
        },
        
        complete: function () {
          showLoadingState(false);
        },
      });
    }

    function getCurrentUrlFilters() {
      const urlParams = new URLSearchParams(window.location.search);
      return {
        property_for: urlParams.get("property_for") || "",
        property_type: urlParams.get("property_type") || "",
        location: urlParams.get("location") || "",
      };
    }

    function updateUrlWithSort(sortType) {
      if (history.pushState) {
        const url = new URL(window.location);
        url.searchParams.set("sort_by", sortType);
        history.pushState({}, "", url.toString());
      }
    }

    function showLoadingState(isLoading) {
      if (isLoading) {
        $loadingIndicator.show();
        $sortDropdown.prop("disabled", true);
        $propertiesContainer.css('opacity', '0.6');
      } else {
        $loadingIndicator.hide();
        $sortDropdown.prop("disabled", false);
        $propertiesContainer.css('opacity', '1');
      }
    }

    function showErrorMessage(message) {
      // Remove existing error messages
      $(".sort-error-message").remove();
      
      // Add error message
      const errorHtml = '<div class="sort-error-message" style="background:#ffe6e6;color:#d32f2f;padding:15px;border-radius:6px;margin:15px 0;">' + message + '</div>';
      $widget.after(errorHtml);
      
      // Auto-remove after 5 seconds
      setTimeout(function () {
        $(".sort-error-message").fadeOut(function () {
          $(this).remove();
        });
      }, 5000);
    }

    // Initialize from URL
    const urlParams = new URLSearchParams(window.location.search);
    const sortParam = urlParams.get("sort_by");
    if (sortParam && $sortDropdown.find('option[value="' + sortParam + '"]').length) {
      $sortDropdown.val(sortParam);
    }

    return {
      performSort: performAjaxSort,
      getCurrentSort: function() {
        return $sortDropdown.val();
      }
    };
  }

  // Global functions for external access
  window.ajaxPropertySortTrigger = function (sortType, widgetSelector) {
    const $targetWidget = widgetSelector ? $(widgetSelector) : $(".ajax-property-sort-widget").first();
    const $dropdown = $targetWidget.find(".sort-dropdown");
    
    if ($dropdown.length) {
      $dropdown.val(sortType).trigger("change");
      return true;
    }
    return false;
  };

  window.ajaxPropertySortGetCurrent = function (widgetSelector) {
    const $targetWidget = widgetSelector ? $(widgetSelector) : $(".ajax-property-sort-widget").first();
    const $dropdown = $targetWidget.find(".sort-dropdown");
    
    return $dropdown.length ? $dropdown.val() : null;
  };
});

// Custom event dispatcher for integration
document.addEventListener("DOMContentLoaded", function () {
  const sortReadyEvent = new CustomEvent("ajaxPropertySortReady", {
    detail: {
      version: "3.0.0",
      method: "AJAX",
      triggerSort: window.ajaxPropertySortTrigger,
      getCurrentSort: window.ajaxPropertySortGetCurrent,
    },
  });
  document.dispatchEvent(sortReadyEvent);
});