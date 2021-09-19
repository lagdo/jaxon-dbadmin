    <form id="<?php echo $this->formId ?>">
        <div class="form-group row adminer-edit-table-header">
            <label class="col-md-2">Table</label>
        </div>
        <div class="form-group row adminer-edit-table-header">
            <div class="col-md-3 adminer-edit-table-name">
                <input type="text" name="name" class="form-control" value="<?php
                    echo $this->table->name ?>" placeholder="Name" />
            </div>
<?php if($this->engines): ?>
            <div class="col-md-2 adminer-edit-table-engine">
                <select name="engine" class="form-control">
                    <option value="">(engine)</option>
<?php foreach($this->engines as $group => $engine): ?>
                    <option <?php if(!strcasecmp($this->table->engine, $engine)): ?>selected<?php
                        endif ?>><?php echo $engine ?></option>
<?php endforeach ?>
                </select>
            </div>
<?php endif ?>
<?php if($this->collations): ?>
            <div class="col-md-3 adminer-edit-table-collation">
                <select name="collation" class="form-control">
                    <option value="" selected>(collation)</option>
<?php foreach($this->collations as $group => $collations): ?>
<?php if(is_string($collations)): ?>
                    <option <?php if($this->table->collation === $collations): ?>selected<?php
                        endif ?>><?php echo $collations ?></option>
<?php else: ?>
                    <optgroup label="<?php echo $group ?>">
<?php foreach($collations as $collation): ?>
                        <option <?php if($this->table->collation === $collation): ?>selected<?php
                            endif ?>><?php echo $collation ?></option>
<?php endforeach ?>
                    </optgroup>
<?php endif ?>
<?php endforeach ?>
                </select>
            </div>
<?php endif ?>
<?php if($this->support['comment']): ?>
            <div class="col-md-4 adminer-table-column-middle">
                <input name="comment" class="form-control" value="<?php
                    echo $this->table->comment ?>" placeholder="<?php
                    echo $this->trans->lang('Comment') ?>" />
            </div>
<?php endif ?>
        </div>
        <div class="form-group row adminer-table-column-header">
            <label class="col-md-3 adminer-table-column-left"><?php echo $this->trans->lang('Column') ?></label>
            <label class="col-md-1 adminer-table-column-null-header" for="autoIncrementCol">
                <input type="radio" name="autoIncrementCol" value="0" <?php
                    if(!$this->options['hasAutoIncrement']): ?>checked <?php endif ?>/> AI
            </label>
            <label class="col-md-7 adminer-table-column-middle"><?php echo $this->trans->lang('Options') ?></label>
            <div class="col-md-1 adminer-table-column-buttons-header">
<?php if($this->support['columns']): ?>
                <button type="button" class="btn btn-primary" id="adminer-table-column-add">
                    <i class="bi bi-plus"></i>
                </button>
<?php endif ?>
            </div>
        </div>
<?php $index = 0; foreach($this->fields as $field): ?>
<?php echo $this->render('adminer::views::table/column', [
    'trans' => $this->trans,
    'class' => $this->formId . '-column',
    'index' => $index,
    'field' => $field,
    'prefixFields' => sprintf("fields[%d]", ++$index),
    'collations' => $this->collations,
    'unsigned' => $this->unsigned,
    'foreignKeys' => $this->foreignKeys,
    'options' => $this->options,
    'support' => $this->support,
]) ?>
<?php endforeach ?>
    </form>
