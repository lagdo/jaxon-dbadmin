<div class="radio">
<?php foreach ($this->values as $item): ?>
    <label><input type="radio"<?php foreach ($this->attrs as $name => $value) {
        echo ' ', $name, '="', $value, '"';
    } ?> value="<?php echo $item['value'] ?>"<?php if ($item['checked']) {
        echo ' checked';
    } ?>><?php echo $item['text'] ?></label>
<?php endforeach; ?>
</div>
