                <div class="list-group">
<?php foreach($this->menu_actions as $id => $title): ?>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action menu-action-<?php
                        echo $id ?>" id="adminer-menu-action-<?php echo $id ?>"><?php echo $title ?></a>
<?php endforeach ?>
                </div>
