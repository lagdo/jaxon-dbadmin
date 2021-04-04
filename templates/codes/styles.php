<style type="text/css">
#<?php echo $this->containerId ?> {
    margin: 20px 0 0 0;
}
#<?php echo $this->containerId ?> .row>[class*=col-] {
    margin-bottom: 10px;
}
#<?php echo $this->containerId ?> .panel-heading {
    padding: 10px 15px;
    border-bottom: 1px solid transparent;
    border-top-right-radius: 3px;
    border-top-left-radius: 3px;
}
#<?php echo $this->containerId ?> .btn-select {
    margin-top: 0;
    margin-bottom: 0;
}
#<?php echo $this->dbListId ?> {
    margin: 10px 0;
}
#<?php echo $this->userInfoId ?>, #<?php echo $this->serverInfoId ?> {
    margin-bottom: 10px;
}
#<?php echo $this->userInfoId ?> .panel, #<?php echo $this->serverInfoId ?> .panel {
    margin-bottom: 0;
}
#<?php echo $this->dbActionsId ?>, #<?php echo $this->serverActionsId ?> {
    margin-bottom: 10px;
}
.adminer-table-checkbox {
    width: 20px;
}
.breadcrumb {
    float: left;
    padding: 5px 10px 5px 10px;
    margin-bottom: 10px;
    margin-top: 11px;
}
/* Disable spinners on inputs with type number */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>
