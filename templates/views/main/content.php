<?php if(is_array($this->headers)): ?>
                    <thead>
                        <tr>
<?php if(isset($this->checkbox)): ?>
                            <th class="dbadmin-table-checkbox"><input id="dbadmin-table-<?php
                                echo $this->checkbox ?>-all" type="checkbox" /></th>
<?php endif ?>
<?php foreach($this->headers as $header): ?>
                            <th><?php echo $header ?></th>
<?php endforeach ?>
                        </tr>
                    </thead>
<?php endif ?>
                    <tbody>
<?php foreach($this->details as $details): ?>
                        <tr>
<?php if(isset($this->checkbox)): ?>
                            <td><input type="checkbox" class="dbadmin-table-<?php echo
                                $this->checkbox ?>" name="<?php echo $this->checkbox ?>[]" /></td>
<?php endif ?>
<?php foreach($details as $detail): ?>
<?php if(is_array($detail)): ?>
                            <td<?php foreach($detail['props'] as $name => $value): ?> <?php
                                echo $name ?>="<?php echo $value ?>"<?php
                                endforeach ?>><?php echo $detail['label'] ?></td>
<?php else: ?>
                            <td><?php echo $detail ?></td>
<?php endif ?>
<?php endforeach ?>
                        </tr>
<?php endforeach ?>
                    </tbody>
