<?php
/**
 * Sticky save bar for the admin grids.
 *
 * Starts quiet. JavaScript counts how many rows have been edited and
 * switches it on, so nothing can be typed and then forgotten. Without
 * JavaScript the Save button is simply always enabled and still works.
 */
?>
<div class="savebar is-clean" data-savebar>
  <span class="savebar-msg">
    <span class="dot" aria-hidden="true"></span>
    <span data-savebar-text>No unsaved changes</span>
  </span>
  <span class="savebar-actions">
    <button type="button" class="btn btn-quiet btn-sm" data-savebar-reset hidden>Discard</button>
    <button form="gridform" type="submit" class="btn btn-primary btn-sm" data-savebar-save>Save changes</button>
  </span>
</div>
