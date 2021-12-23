<div class="checkbox">
<?php foreach ($this->values as $value): ?>
    <label><input type="checkbox" name="<?php echo $this->attrs['name'] ?>[<?php echo $value['value'] ?>]"<?php
        if ($value['checked']) { echo ' checked'; } ?>><?php echo $value['text'] ?></label>
<?php endforeach; ?>
</div>
