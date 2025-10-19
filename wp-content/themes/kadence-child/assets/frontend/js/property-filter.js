jQuery(document).ready(function ($) {
  // Tab functionality
  $(".tab-link").on("click", function (e) {
    e.preventDefault();

    // Remove active class from all tabs
    $(".tab-link").removeClass("active");

    // Add active class to clicked tab
    $(this).addClass("active");

    // Update hidden input value
    const filterType = $(this).data("filter-type");
    $("#filter-type-input").val(filterType);
  });

  // Form submission handler
  $("#property-filter-form").on("submit", function (e) {
    // Let the form submit normally to redirect to archive page
    // The Elementor widget will automatically filter based on URL parameters
  });

  // Initialize the form with current URL parameters
  initializeFormFromURL();

  // Initialize custom select dropdowns
  initializeCustomSelect();

  // Apply custom select style (you can change this to switch styles)
  applySelectStyle("default"); // Options: 'default', 'modern', 'minimal', 'rounded'

  function initializeFormFromURL() {
    const urlParams = new URLSearchParams(window.location.search);

    // Set filter type tab
    const propertyFor = urlParams.get("property_for");
    if (propertyFor) {
      $(".tab-link").removeClass("active");
      $(`.tab-link[data-filter-type="${propertyFor}"]`).addClass("active");
      $("#filter-type-input").val(propertyFor);
    }

    // Set location input
    const location = urlParams.get("location");
    if (location) {
      $("#filter-location").val(location);
    }

    // Set property type select
    const propertyType = urlParams.get("property_type");
    if (propertyType) {
      $("#filter-property-type").val(propertyType);
    }
  }

  function initializeCustomSelect() {
    // Convert existing select elements to custom selects
    $(".property-type-field select").each(function () {
      const $select = $(this);
      const $field = $select.closest(".form-field");

      // Skip if already converted
      if ($field.find(".custom-select-wrapper").length) return;

      // Create custom select wrapper
      const $wrapper = $('<div class="custom-select-wrapper"></div>');
      const $customSelect = $('<div class="custom-select"></div>');

      // Get current value and text
      const currentValue = $select.val();
      const currentText = currentValue
        ? $select.find("option:selected").text()
        : $select.find("option:first").text();

      // Create trigger
      const $trigger = $(`
        <div class="custom-select-trigger ${currentValue ? "has-value" : ""}">
          <span class="custom-select-text">${currentText}</span>
        </div>
      `);

      // Create options container
      const $optionsContainer = $('<div class="custom-select-options"></div>');

      // Add options
      $select.find("option").each(function () {
        const $option = $(this);
        const value = $option.val();
        const text = $option.text();
        const isSelected = $option.is(":selected") || currentValue === value;

        const $customOption = $(`
          <div class="custom-select-option ${
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
    const $trigger = $customSelect.find(".custom-select-trigger");
    const $options = $customSelect.find(".custom-select-option");
    const $text = $customSelect.find(".custom-select-text");

    // Toggle dropdown
    $trigger.on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Close other dropdowns
      $(".custom-select.active").not($customSelect).removeClass("active");

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

    // Handle keyboard navigation
    $customSelect.on("keydown", function (e) {
      if (!$customSelect.hasClass("active")) return;

      const $selectedOption = $options.filter(".selected");
      let $newOption = null;

      switch (e.key) {
        case "ArrowDown":
          e.preventDefault();
          $newOption = $selectedOption.length
            ? $selectedOption.next()
            : $options.first();
          if (!$newOption.length) $newOption = $options.first();
          break;
        case "ArrowUp":
          e.preventDefault();
          $newOption = $selectedOption.length
            ? $selectedOption.prev()
            : $options.last();
          if (!$newOption.length) $newOption = $options.last();
          break;
        case "Enter":
          e.preventDefault();
          if ($selectedOption.length) {
            $selectedOption.click();
          }
          break;
      }

      if ($newOption && $newOption.length) {
        $options.removeClass("selected");
        $newOption.addClass("selected");

        // Scroll option into view
        const containerHeight = $customSelect
          .find(".custom-select-options")
          .height();
        const optionTop = $newOption.position().top;
        const optionHeight = $newOption.outerHeight();

        if (optionTop < 0) {
          $customSelect
            .find(".custom-select-options")
            .scrollTop(
              $customSelect.find(".custom-select-options").scrollTop() +
                optionTop
            );
        } else if (optionTop + optionHeight > containerHeight) {
          $customSelect
            .find(".custom-select-options")
            .scrollTop(
              $customSelect.find(".custom-select-options").scrollTop() +
                optionTop +
                optionHeight -
                containerHeight
            );
        }
      }
    });

    // Make the custom select focusable
    $customSelect.attr("tabindex", "0");
  }

  function applySelectStyle(style) {
    const $field = $(".property-type-field");

    // Remove all style classes
    $field.removeClass(
      "select-style-modern select-style-minimal select-style-rounded"
    );

    // Apply selected style
    switch (style) {
      case "modern":
        $field.addClass("select-style-modern");
        break;
      case "minimal":
        $field.addClass("select-style-minimal");
        break;
      case "rounded":
        $field.addClass("select-style-rounded");
        break;
      case "default":
      default:
        // Default styling (no additional class)
        break;
    }
  }

  // Utility function to change select style dynamically (for testing)
  window.changeSelectStyle = function (style) {
    applySelectStyle(style);
  };
});
