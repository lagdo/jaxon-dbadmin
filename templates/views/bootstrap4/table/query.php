<form id="<?php echo $this->formId ?>">
<?php foreach($this->fields as $name => $field): ?>
        <div class="form-group row">
            <div class="col-md-3">
                <label title="<?php echo $field['type'] ?>"><?php echo $field['name'] ?></label>
            </div>
            <div class="col-md-2">
<?php if($field['functions']['type'] === 'name'): ?>
                <label class=""><?php echo $field['functions']['name'] ?></label>
<?php elseif($field['functions']['type'] === 'select'): ?>
                <select name="<?php echo $field['functions']['name'] ?>" class="form-control">
<?php foreach($field['functions']['options'] as $function): ?>
                    <option <?php if($function === $field['functions']['selected']): ?>selected<?php
                        endif ?>><?php echo $function ?></option>
<?php endforeach ?>
                </select>
<?php endif ?>
            </div>
            <div class="col-md-7">
<?php echo $this->render('adminer::templates::table/input/' . $field['input']['type'], $field['input']) ?>
            </div>
        </div>
<?php endforeach ?>
    </form>
