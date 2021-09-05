<form action="" method="post">
    <p>
    " . ($numRows ? ($limit && $numRows > $limit ?
                                    $this->trans->lang("%d / ", $limit) : "") . $this->trans->lang("%d row(s)", $numRows) : "");
                                echo $time;
                                if($connection && \preg_match("~^($space|\\()*+SELECT\\b~i", $q) &&
                                    ($explain = \adminer\explain($connection, $q))) {
                                    echo ", <a href="#$explain_id">Explain</a>" .
                                        \adminer\script("qsl("a").onclick = partial(toggle, "$explain_id");", "");
                                }
                                $id = "export-$commands";
                                echo ", <a href="#$id">" . $this->trans->lang("Export") . "</a>" .
                                    \adminer\script("qsl("a").onclick = partial(toggle, "$id");", "") .
    <span id="$id" class="hidden">: "
                                    . \adminer\html_select("output", $adminer->dumpOutput(), $adminer_export["output"]) . " "
                                    . \adminer\html_select("format", $dump_format, $adminer_export["format"])
        <input type="hidden" name="query" value="" \adminer\h($q) >
        <input type="submit" name="export" value="" $this->trans->lang("Export") />
        <input type="hidden" name="token" value="$token">
    </span>
</form>
