            <form class="form-horizontal" role="form" id="<?php echo $this->formId ?>">
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group">
                            <label for="output" class="col-md-3 control-label"><?php
                                echo $this->options['output']['label'] ?></label>
                            <div class="col-md-8">
<?php foreach($this->options['output']['options'] as $value => $label): ?>
                                <label class="radio-inline">
                                    <input type="radio" name="output" value="<?php echo $value ?>" <?php
                                        if($this->options['output']['value'] === $value): ?>checked <?php
                                        endif ?>/><?php echo $label ?>
                                </label>
<?php endforeach ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="format" class="col-md-3 control-label"><?php
                                echo $this->options['format']['label'] ?></label>
                            <div class="col-md-8">
<?php foreach($this->options['format']['options'] as $value => $label): ?>
                                <label class="radio-inline">
                                    <input type="radio" name="format" value="<?php echo $value ?>" <?php
                                        if($this->options['format']['value'] === $value): ?>checked <?php
                                        endif ?>/><?php echo $label ?>
                                </label>
<?php endforeach ?>
                            </div>
                        </div>
<?php if(array_key_exists('db_style', $this->options)): ?>
                        <div class="form-group">
                            <label for="db_style" class="col-md-3 control-label"><?php
                                echo $this->options['db_style']['label'] ?></label>
                            <div class="col-md-8">
                                <select name="db_style" class="form-control">
<?php foreach($this->options['db_style']['options'] as $label): ?>
                                    <option <?php
                                        if($this->options['db_style']['value'] == $label): ?>selected<?php
                                        endif ?>><?php echo $label ?></option>
<?php endforeach ?>
                                </select>
                            </div>
                        </div>
<?php if(array_key_exists('routines', $this->options) || array_key_exists('events', $this->options)): ?>
                        <div class="form-group">
                            <label class="col-md-3 control-label">&nbsp;</label>
<?php if(array_key_exists('routines', $this->options)): ?>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label><input type="checkbox" name="routines" value="<?php
                                        echo $this->options['routines']['value'] ?>" <?php
                                        if($this->options['routines']['checked']): ?>checked <?php
                                        endif ?>/><?php echo $this->options['routines']['label'] ?></label>
                                </div>
                            </div>
<?php endif ?>
<?php if(array_key_exists('events', $this->options)): ?>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label><input type="checkbox" name="events" value="<?php
                                        echo $this->options['events']['value'] ?>" <?php
                                        if($this->options['events']['checked']): ?>checked <?php
                                        endif ?>/><?php echo $this->options['events']['label'] ?></label>
                                </div>
                            </div>
<?php endif ?>
                        </div>
<?php endif ?>
<?php endif ?>
                        <div class="form-group">
                            <label for="table_style" class="col-md-3 control-label"><?php
                                echo $this->options['table_style']['label'] ?></label>
                            <div class="col-md-8">
                                <select name="table_style" class="form-control">
<?php foreach($this->options['table_style']['options'] as $label): ?>
                                    <option <?php
                                        if($this->options['table_style']['value'] == $label): ?>selected<?php
                                        endif ?>><?php echo $label ?></option>
<?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">&nbsp;</label>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label><input type="checkbox" name="auto_increment" value="<?php
                                        echo $this->options['auto_increment']['value'] ?>" <?php
                                        if($this->options['auto_increment']['checked']): ?>checked <?php
                                        endif ?>/><?php echo $this->options['auto_increment']['label'] ?></label>
                                </div>
                            </div>
<?php if(array_key_exists('triggers', $this->options)): ?>
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label><input type="checkbox" name="triggers" value="<?php
                                        echo $this->options['triggers']['value'] ?>" <?php
                                        if($this->options['triggers']['checked']): ?>checked <?php
                                        endif ?>/><?php echo $this->options['triggers']['label'] ?></label>
                                </div>
                            </div>
<?php endif ?>
                        </div>
                        <div class="form-group">
                            <label for="data_style" class="col-md-3 control-label"><?php
                                echo $this->options['data_style']['label'] ?></label>
                            <div class="col-md-8">
                                <select name="data_style" class="form-control">
<?php foreach($this->options['data_style']['options'] as $label): ?>
                                    <option <?php
                                        if($this->options['data_style']['value'] == $label): ?>selected<?php
                                        endif ?>><?php echo $label ?></option>
<?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-4 col-md-offset-3">
                                <button class="btn btn-primary btn-block" type="button" href="javascript:void(0)"><?php
                                    echo $this->submit ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
<?php if(isset($this->databases)): ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="adminer-export-database-all" checked />
                                            <?php echo $this->databases['headers'][0] ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach($this->databases['details'] as $database): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="adminer-export-database" name="database[]" checked />
                                            <?php echo $database['name'] ?>
                                        </td>
                                    </tr>
<?php endforeach ?>
                                </tbody>
                            </table>
<?php endif ?>
<?php if(isset($this->tables)): ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="adminer-export-table-name-all" checked />
                                            <?php echo $this->tables['headers'][0] ?>
                                        </th>
                                        <th>
                                            <input type="checkbox" id="adminer-export-table-data-all" checked />
                                            <?php echo $this->tables['headers'][1] ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach($this->tables['details'] as $table): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="adminer-export-table-name" name="table-name[]" checked />
                                            <?php echo $table['name'] ?>
                                        </td>
                                        <td>
                                            <input type="checkbox" class="adminer-export-table-data" name="table-data[]" checked />
                                        </td>
                                    </tr>
<?php endforeach ?>
                                </tbody>
                            </table>
<?php endif ?>
                        </div>
                    </div>
                </div>
            </form>
