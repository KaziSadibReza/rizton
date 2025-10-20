jQuery(document).ready(function ($) {
  // Initialize custom dropdown for property sort
  initializeCustomSelect();

  function initializeCustomSelect() {
    // Convert existing select elements to custom selects
    $(".property-sort-widget select").each(function () {
      const $select = $(this);
      const $widget = $select.closest(".property-sort-widget");

      // Skip if already converted
      if ($widget.find(".property-sort-select-wrapper").length) return;

      // Create custom select wrapper
      const $wrapper = $('<div class="property-sort-select-wrapper"></div>');
      const $customSelect = $(
        '<div class="property-sort-select-custom"></div>'
      );

      // Get current value and text
      const currentValue = $select.val();
      const currentText = currentValue
        ? $select.find("option:selected").text()
        : $select.find("option:first").text();

      // Create trigger
      const $trigger = $(`
        <div class="property-sort-select-trigger ${
          currentValue ? "has-value" : ""
        }">
          <span class="property-sort-select-text">${currentText}</span>
        </div>
      `);

      // Create options container
      const $optionsContainer = $(
        '<div class="property-sort-select-options"></div>'
      );

      // Add options
      $select.find("option").each(function () {
        const $option = $(this);
        const value = $option.val();
        const text = $option.text();
        const isSelected = $option.is(":selected") || currentValue === value;

        const $customOption = $(`
          <div class="property-sort-select-option ${
            isSelected ? "selected" : ""
          }" data-value="${value}">
            ${text}
          </div>
        `);

        $optionsContainer.append($customOption);
      });

      // Assemble custom select
      $customSelect.append($trigger).append($optionsContainer);
      $wrapper.append($customSelect);

      // Hide original select and add custom select
      $select.hide();
      $select.after($wrapper);

      // Add event handlers
      setupCustomSelectEvents($customSelect, $select);
    });
  }

  function setupCustomSelectEvents($customSelect, $originalSelect) {
    const $trigger = $customSelect.find(".property-sort-select-trigger");
    const $options = $customSelect.find(".property-sort-select-option");
    const $text = $customSelect.find(".property-sort-select-text");

    // Toggle dropdown
    $trigger.on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Close other dropdowns
      $(".property-sort-select-custom.active")
        .not($customSelect)
        .removeClass("active");

      // Toggle this dropdown
      $customSelect.toggleClass("active");
    });

    // Option selection
    $options.on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $option = $(this);
      const value = $option.data("value");
      const text = $option.text();

      // Update custom select appearance
      $options.removeClass("selected");
      $option.addClass("selected");
      $text.text(text);

      // Update trigger class
      if (value) {
        $trigger.addClass("has-value");
      } else {
        $trigger.removeClass("has-value");
      }

      // Update original select
      $originalSelect.val(value).trigger("change");

      // Handle sort change with AJAX
      handleAjaxSort(value);

      // Close dropdown
      $customSelect.removeClass("active");
    });

    // Close dropdown when clicking outside
    $(document).on("click", function (e) {
      if (
        !$customSelect.is(e.target) &&
        $customSelect.has(e.target).length === 0
      ) {
        $customSelect.removeClass("active");
      }
    });

    // Close dropdown on escape key
    $(document).on("keydown", function (e) {
      if (e.key === "Escape") {
        $customSelect.removeClass("active");
      }
    });

    // Make the custom select focusable
    $customSelect.attr("tabindex", "0");
  }

  // AJAX sort handling function
  function handleAjaxSort(sortValue) {
    // Get current URL parameters to preserve existing filters
    const currentParams = window.location.search.substring(1);

    // Prepare AJAX data
    const ajaxData = {
      action: "ajax_property_sort",
      sort_by: sortValue,
      current_params: currentParams,
      paged: 1, // Reset to first page when sorting
      nonce: getAjaxNonce(),
    };

    console.log("Making AJAX request with data:", ajaxData);

    // Make AJAX request
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: ajaxData,
      dataType: "json",
      success: function (response) {
        console.log("AJAX Success Response:", response);
        if (response.success) {
          // Update URL with new sort parameter
          updateUrlWithoutReload(sortValue);

          // Try to refresh Elementor widget content
          refreshElementorWidget();
        } else {
          console.error("AJAX request failed - Invalid response:", response);
          showErrorMessage("Failed to sort properties. Please try again.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", {
          status,
          error,
          response: xhr.responseText,
        });
        showErrorMessage("Failed to sort properties. Please try again.");
      },
    });
  }

  // Find the properties container (this should match your Elementor loop container)
  function findPropertiesContainer() {
    // Try different possible selectors for the properties container
    const selectors = [
      ".elementor-widget-loop-grid .elementor-loop-container",
      ".elementor-loop-container",
      ".property-loop-container",
      ".properties-grid",
      ".property-cards-container",
      '[data-widget_type="loop-grid.default"] .elementor-loop-container',
    ];

    for (let selector of selectors) {
      const $container = $(selector);
      if ($container.length > 0) {
        return $container;
      }
    }

    // Fallback: return first element that might contain properties
    return $(".elementor-widget-container").first();
  }

  // Update properties container with new content
  function updatePropertiesContainer(html) {
    const $container = findPropertiesContainer();
    if ($container.length) {
      $container.html(html);
    }
  }

  // Update pagination (if you have pagination)
  function updatePagination(maxPages, currentPage) {
    // This is optional - implement if you have pagination
    // You would need to identify your pagination container and update it
  }

  // Update URL without page reload
  function updateUrlWithoutReload(sortValue) {
    const urlParams = new URLSearchParams(window.location.search);

    if (sortValue) {
      urlParams.set("sort_by", sortValue);
    } else {
      urlParams.delete("sort_by");
    }

    const newUrl = window.location.pathname + "?" + urlParams.toString();

    // Update browser history without reloading
    if (history.pushState) {
      history.pushState({}, "", newUrl);
    }
  }

  // Update results count (if you have a results counter)
  function updateResultsCount(count) {
    const $counter = $(".results-count, .properties-count");
    if ($counter.length) {
      $counter.text(count + " properties found");
    }
  }

  // Scroll to results after sorting
  function scrollToResults() {
    const $container = findPropertiesContainer();
    if ($container.length) {
      $("html, body").animate(
        {
          scrollTop: $container.offset().top - 100,
        },
        500
      );
    }
  }

  // Show error message to user
  function showErrorMessage(message) {
    const $propertiesContainer = findPropertiesContainer();
    if ($propertiesContainer.length) {
      // Remove any existing error messages
      $propertiesContainer.find(".properties-error-message").remove();

      // Add error message
      $propertiesContainer.prepend(
        '<div class="properties-error-message" style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px;">' +
          "<strong>Error:</strong> " +
          message +
          "</div>"
      );

      // Auto-hide after 5 seconds
      setTimeout(function () {
        $propertiesContainer.find(".properties-error-message").fadeOut();
      }, 5000);
    } else {
      // Fallback to browser alert if container not found
      alert(message);
    }
  }

  // Refresh Elementor widget content using proper Elementor hooks
  function refreshElementorWidget() {
    console.log("Attempting to refresh Elementor widget using hooks...");

    // Method 1: Use Elementor's built-in refresh system
    if (typeof elementorFrontend !== "undefined") {
      // Find the loop widget containers
      const $loopWidgets = $('[data-elementor-query-id="6969"]');
      const $postsContainers = $(
        ".elementor-posts-container, .elementor-posts, .e-loop-container, .elementor-loop-container"
      );

      // Method A: Try to refresh the specific loop widget
      if ($loopWidgets.length) {
        console.log("Found loop widget, using Elementor hooks...");

        $loopWidgets.each(function () {
          const $widget = $(this).closest(".elementor-widget");
          const widgetType = $widget.attr("data-widget_type") || "loop-grid";

          try {
            // Trigger Elementor's widget refresh hooks
            if (elementorFrontend.hooks) {
              // Trigger the specific widget type hook
              elementorFrontend.hooks.doAction(
                "frontend/element_ready/" + widgetType,
                $widget,
                $
              );
              console.log("Triggered hook for widget type:", widgetType);
            }

            // Use runReadyTrigger for reinitialization
            if (
              elementorFrontend.elementsHandler &&
              elementorFrontend.elementsHandler.runReadyTrigger
            ) {
              elementorFrontend.elementsHandler.runReadyTrigger($widget[0]);
              console.log("Triggered runReadyTrigger for widget");
            }
          } catch (error) {
            console.log("Elementor hook error:", error);
          }
        });

        // Trigger custom refresh events that some widgets might listen to
        $loopWidgets.trigger("elementor:widget:refresh");
        $loopWidgets.trigger("elementor:loop:refresh");
      }

      // Method B: Try to refresh posts containers directly
      else if ($postsContainers.length) {
        console.log("Found posts containers, triggering refresh...");

        $postsContainers.each(function () {
          const $container = $(this);
          const $widget = $container.closest(".elementor-widget");
          const widgetType = $widget.attr("data-widget_type") || "posts";

          try {
            if (elementorFrontend.hooks) {
              elementorFrontend.hooks.doAction(
                "frontend/element_ready/" + widgetType,
                $widget,
                $
              );
            }

            if (
              elementorFrontend.elementsHandler &&
              elementorFrontend.elementsHandler.runReadyTrigger
            ) {
              elementorFrontend.elementsHandler.runReadyTrigger($widget[0]);
            }
          } catch (error) {
            console.log("Posts container hook error:", error);
          }
        });

        // Trigger specific posts refresh events
        $postsContainers.trigger("elementor:posts:refresh");
        $postsContainers.trigger("posts:refresh");
      }

      // Method C: Global Elementor refresh triggers
      try {
        // Trigger global refresh events
        $(document).trigger("elementor:refresh");
        $(window).trigger("elementor:loaded");

        // Force a resize event which many widgets respond to
        $(window).trigger("resize");

        console.log("Triggered global Elementor refresh events");
      } catch (error) {
        console.log("Global refresh error:", error);
      }
    } else {
      console.log("elementorFrontend not available");
    }

    // If the hooks don't work, fall back to the partial reload method
    setTimeout(function () {
      // Check if the content actually changed
      const currentUrl = window.location.href;

      // Give hooks time to work, then check if we need fallback
      console.log("Checking if hooks worked, fallback if needed...");

      // If hooks didn't work, use the working partial reload method
      fallbackToPartialReload(currentUrl);
    }, 1000); // Give hooks 1 second to work

    console.log("Widget refresh attempt using hooks completed");
  }

  // Fallback method using partial page reload (our working solution)
  function fallbackToPartialReload(currentUrl) {
    console.log("Using fallback partial reload method...");

    $.get(currentUrl)
      .done(function (data) {
        const $newContent = $(data);
        const $currentPosts = $(
          ".elementor-posts-container, .elementor-posts, .e-loop-container, .elementor-loop-container"
        );
        const $newPosts = $newContent.find(
          ".elementor-posts-container, .elementor-posts, .e-loop-container, .elementor-loop-container"
        );

        if ($currentPosts.length && $newPosts.length) {
          console.log("Fallback: Replacing posts container content");
          $currentPosts.first().replaceWith($newPosts.first());

          // Reinitialize Elementor on new content
          if (
            typeof elementorFrontend !== "undefined" &&
            elementorFrontend.elementsHandler
          ) {
            const newWidgetElement = $(
              ".elementor-posts-container, .elementor-posts, .e-loop-container, .elementor-loop-container"
            ).closest(".elementor-widget")[0];
            if (newWidgetElement) {
              elementorFrontend.elementsHandler.runReadyTrigger(
                newWidgetElement
              );
            }
          }

          console.log("Fallback method successful");
        }
      })
      .fail(function () {
        console.log("Fallback method also failed");
      });
  }

  // Get AJAX URL
  function getAjaxUrl() {
    // Try to get from localized script first
    if (typeof ajax_object !== "undefined" && ajax_object.ajax_url) {
      return ajax_object.ajax_url;
    }

    // Fallback to WordPress AJAX URL
    return "/wp-admin/admin-ajax.php";
  }

  // Get AJAX nonce
  function getAjaxNonce() {
    // Try to get from localized script first
    if (typeof ajax_object !== "undefined" && ajax_object.nonce) {
      return ajax_object.nonce;
    }

    // Fallback nonce
    return "property_sort_nonce";
  }
});
