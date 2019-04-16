<?php
/**
 * Mattermost Integration
 * Copyright (C) AA'LA Solutions (info@aalasolutions.com)
 *
 * Mattermost Integration is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * Mattermost Integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Mattermost Integration; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 * or see http://www.gnu.org/licenses/.
 */

access_ensure_global_level(config_get('manage_plugin_threshold'));

layout_page_header(plugin_lang_get('title'));

layout_page_begin('manage_overview_page.php');

print_manage_menu('manage_plugin_page.php');

?>
    <div class="col-md-12 col-xs-12">
        <div class="space-10"></div>
        <div class="form-container">
            <form action="<?php echo plugin_page('config') ?>" method="post">
                <fieldset>
                    <div class="widget-box widget-color-blue2">
                        <div class="widget-header widget-header-small">
                            <h4 class="widget-title lighter">
                                <i class="ace-icon fa fa-exchange"></i>
                                <?php echo plugin_lang_get('title') ?>
                            </h4>
                        </div>
                        <?php echo form_security_field('plugin_Mattermost_config') ?>
                        <div class="widget-body">
                            <div class="widget-main no-padding">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-condensed table-striped">

                                        <tr>
                                            <td class="category">
                                                <strong><?php echo plugin_lang_get('url_webhook') ?></strong>
                                            </td>
                                            <td>
                                                <input size="80" type="text" name="url_webhook"
                                                       value="<?php echo plugin_config_get('url_webhook') ?>"/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="category">
                                                <?php echo plugin_lang_get('default_channel') ?>
                                            </td>
                                            <td colspan="2">
                                                <input type="text" name="default_channel"
                                                       value="<?php echo plugin_config_get('default_channel') ?>"/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="category">
                                                <?php echo plugin_lang_get('bot_name') ?>
                                            </td>
                                            <td colspan="2">
                                                <input type="text" name="bot_name"
                                                       value="<?php echo plugin_config_get('bot_name') ?>"/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="category">
                                                <?php echo plugin_lang_get('bot_icon') ?>
                                            </td>
                                            <td colspan="2">
                                                <p>
                                                    Kann entweder eine URL sein, die auf ein kleines Bild zeigt, oder ein Emoji der Form
                                                    :emoji:</br>
                                                    Standardmäßig ist das Mantis-Logo voreingestellt.
                                                </p>
                                                <input type="text" name="bot_icon"
                                                       value="<?php echo plugin_config_get('bot_icon') ?>"/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="category">
                                                <?php echo plugin_lang_get('skip_bulk') ?>
                                            </td>
                                            <td colspan="2">
                                                <input type="checkbox"
                                                       name="skip_bulk" <?php if (plugin_config_get('skip_bulk')) {
                                                    echo "checked";
                                                } ?> />
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="category">
                                                <?php echo plugin_lang_get('link_names') ?>
                                            </td>
                                            <td colspan="2">
                                                <input type="checkbox"
                                                       name="link_names" <?php if (plugin_config_get('link_names')) {
                                                    echo "checked";
                                                } ?> />
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="category">
                                                <?php echo plugin_lang_get('columns') ?>
                                            </td>
                                            <td colspan="2">
                                                <p>
                                                    Gibt die Fehlerfelder an, die an das Mattermost angehängt werden sollen.
                                                    Benachrichtigungen.
                                                </p>
                                                <p>
                                                    Der Optionsname ist <stark>plugin_Mattermost_columns</stark> und ist ein
                                                    Reihe
                                                    von Fehlerspaltennamen.
                                                    Array-Optionen müssen über die <a href="adm_config_report.php">Konfiguration eingestellt werden.</a>
                                                    <?php
                                                    $t_columns = columns_get_all(@$t_project_id);
                                                    $t_all = implode(', ', $t_columns);
                                                    ?>
                                                    Verfügbare Spaltennamen sind:
                                                <div><textarea name="all_columns" readonly="readonly" cols="80"
                                                               rows="5"><?php echo $t_all ?></textarea></div>
                                                Der aktuelle Wert dieser Option ist:
                                                <pre><?php var_export(plugin_config_get('columns')) ?></pre>
                                                </p>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="category">
                                                <?php echo plugin_lang_get('usernames') ?>
                                            </td>
                                            <td colspan="2">
                                                <p>
                                                    Gibt die Zuordnung zwischen Mantis und Mattermost Benutzernamen an.
                                                </p>
                                                <p>
                                                    Der Optionsname ist <stark>plugin_Mattermost_usernames</stark> und ist
                                                    ein
                                                    Array of 'Mantis Benutzername' => 'Mattermost Benutzername'.
                                                    Alle Benutzernamen ohne Mapping werden 1:1 von Mantis an Mattermost weitergegeben. Dieses Array kann verwendet werden,
                                                    um unterschiedliche Benutzernamen in den Systemen zu korrigieren oder um bestimmte Nachrichten an andere Mattermost
                                                    Benutzer umzuleiten.
                                                    Array-Optionen müssen über die <a href="adm_config_report.php">Konfiguration eingestellt werden.</a>
                                                    Der aktuelle Wert dieser Option ist:
                                                <pre><?php var_export(plugin_config_get('usernames')) ?></pre>
                                                </p>
                                            </td>
                                        </tr>

                                    </table>
                                </div>
                            </div>
                            <div class="widget-toolbox padding-8 clearfix">
                                <input type="submit" class="btn btn-primary btn-white btn-round"
                                       value="<?php echo plugin_lang_get('action_update') ?>"/>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

<?php
layout_page_end();
