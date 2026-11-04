<?php
// Review form for a customer with a paid order. Expects $ROOT_DIR.
?>
<form action="<?php echo $ROOT_DIR; ?>/actions/submit_review_action.php" method="POST" class="form-card review-form stack" data-pending="Sending…">
    <?php echo csrf_field(); ?>
    <h3 class="section-title-sm">Write a review</h3>
    <fieldset class="star-input">
        <legend>Your rating</legend>
        <div class="star-input-row">
            <?php for ($star = 5; $star >= 1; $star--): ?>
                <input type="radio" name="rating" id="star-<?php echo $star; ?>" value="<?php echo $star; ?>" class="visually-hidden" required>
                <label for="star-<?php echo $star; ?>"><span aria-hidden="true">★</span><span class="visually-hidden"><?php echo $star; ?> out of 5 stars</span></label>
            <?php endfor; ?>
        </div>
    </fieldset>
    <label class="field">
        <span>Your review</span>
        <textarea name="review_text" class="input" rows="4" minlength="10" maxlength="500" required></textarea>
    </label>
    <p class="small muted">We show your first name and last initial. The Casely team approves reviews before they appear.</p>
    <button type="submit" class="btn btn-primary">Send review</button>
</form>
