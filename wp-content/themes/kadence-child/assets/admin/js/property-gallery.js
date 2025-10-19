jQuery(document).ready(function ($) {
  var gallery_frame;
  var galleryContainer = $("#property-gallery-images");
  var galleryIds = $("#property-gallery-ids");

  // Make gallery sortable
  galleryContainer.sortable({
    placeholder: "ui-sortable-placeholder",
    tolerance: "pointer",
    cursor: "move",
    update: function () {
      updateGalleryIds();
    },
  });

  // Add images button
  $("#add-gallery-images").on("click", function (e) {
    e.preventDefault();

    // Create new frame each time for better control
    gallery_frame = wp.media({
      title: "Select Property Images",
      button: {
        text: "Update Gallery",
      },
      multiple: "add",
      library: {
        type: "image",
      },
    });

    // Pre-select existing images when frame opens
    gallery_frame.on("open", function () {
      var selection = gallery_frame.state().get("selection");
      var existingIds = getExistingImageIds();

      // Add existing images to selection
      existingIds.forEach(function (imageId) {
        var attachment = wp.media.attachment(imageId);
        attachment.fetch();
        selection.add(attachment);
      });
    });

    gallery_frame.on("select", function () {
      var selection = gallery_frame.state().get("selection");
      var selectedIds = [];
      var existingIds = getExistingImageIds();

      // Get all currently selected image IDs
      selection.each(function (attachment) {
        selectedIds.push(attachment.id.toString());
      });

      // Clear current gallery
      galleryContainer.empty();

      // Add all selected images to gallery
      selection.each(function (attachment) {
        var attachmentData = attachment.toJSON();
        var thumbnailUrl = attachmentData.url;

        // Try to get thumbnail size if available
        if (attachmentData.sizes && attachmentData.sizes.thumbnail) {
          thumbnailUrl = attachmentData.sizes.thumbnail.url;
        } else if (attachmentData.sizes && attachmentData.sizes.medium) {
          thumbnailUrl = attachmentData.sizes.medium.url;
        }

        addImageToGallery(attachmentData.id, thumbnailUrl);
      });

      updateGalleryIds();
    });

    gallery_frame.open();
  });

  // Remove image
  $(document).on("click", ".remove-gallery-image", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this)
      .closest(".gallery-image")
      .fadeOut(200, function () {
        $(this).remove();
        updateGalleryIds();
      });
  });

  function getExistingImageIds() {
    var ids = [];
    galleryContainer.find(".gallery-image").each(function () {
      ids.push($(this).data("image-id").toString());
    });
    return ids;
  }

  function addImageToGallery(imageId, thumbnailUrl) {
    var imageHtml =
      '<div class="gallery-image" data-image-id="' +
      imageId +
      '">' +
      '<img src="' +
      thumbnailUrl +
      '" alt="">' +
      '<div class="gallery-image-actions">' +
      '<span class="remove-gallery-image" data-image-id="' +
      imageId +
      '">&times;</span>' +
      "</div>" +
      "</div>";
    galleryContainer.append(imageHtml);
  }

  function updateGalleryIds() {
    var ids = [];
    galleryContainer.find(".gallery-image").each(function () {
      ids.push($(this).data("image-id"));
    });
    galleryIds.val(ids.join(","));

    // Toggle empty state
    if (ids.length === 0) {
      galleryContainer.addClass("empty");
    } else {
      galleryContainer.removeClass("empty");
    }
  }

  // Initialize empty state
  updateGalleryIds();
});
