<textarea class="form-control"<?php foreach ($this->attrs as $name => $value) { echo ' ', $name, '="', $value, '"'; } ?>>
  <?php echo $this->value ?>
</textarea>
