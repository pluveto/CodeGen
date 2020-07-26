<?= "<?php\n" ?>
return <?php echo var_export_format(
            [
                "API_RULES" => $apiRules,
                "API_PERMISSIONS" => $apiPerms
            ]
        ) ?>;