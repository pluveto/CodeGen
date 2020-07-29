<?= "<?php\n" ?>
return <?php echo var_export_format(
            [
                "rules" => $this->apiRules,
                "permissions" => $this->apiPerms
            ]
        ) ?>;