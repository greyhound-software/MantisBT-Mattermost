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

class MattermostPlugin extends MantisPlugin {
  private $skip = false;
  private $oldBug = false;

  public function register() {
    $this->name = plugin_lang_get('title');
    $this->description = plugin_lang_get('description');
    $this->page = 'config_page';
    $this->version = '1.0';
    $this->requires = array('MantisCore' => '2.5.x');
    $this->author = 'GREYHOUND Software GmbH & Co. KG';
    $this->contact = 'develop@greyhound-software.com';
    $this->url = 'https://greyhound-software.com';
  }

  public function install() {
    if (version_compare(PHP_VERSION, '5.3.0', '<')) {
      plugin_error(ERROR_PHP_VERSION, ERROR);
      return false;
    }
    if (!extension_loaded('curl')) {
      plugin_error(ERROR_NO_CURL, ERROR);
      return false;
    }
    return true;
  }

  public function config() {
    return array(
      'url_webhooks' => array(),
      'url_webhook' => '',
      'bot_name' => 'mantis',
      'bot_icon' => '',
      'skip_bulk' => true,
      'link_names' => false,
      'channels' => array(),
      'default_channel' => '#general',
      'usernames' => array(),
      'columns' => array(
        'status',
        'priority',
        'handler_id',
        'reporter_id',
        'target_version',
        'severity',
        'description',
      ),
    );
  }

  public function hooks() {
    return array(
      'EVENT_REPORT_BUG_DATA' => 'bug_report_data',
      'EVENT_UPDATE_BUG_DATA' => 'bug_update_data',
      'EVENT_REPORT_BUG' => 'bug_report',
      'EVENT_UPDATE_BUG' => 'bug_update',
      'EVENT_BUG_DELETED' => 'bug_deleted',
      'EVENT_BUG_ACTION' => 'bug_action',
      'EVENT_BUG_ACTIONGROUP_FORM' => 'bugnote_add_form',
      'EVENT_BUGNOTE_ADD' => 'bugnote_add_edit',
      'EVENT_BUGNOTE_EDIT' => 'bugnote_add_edit',
      'EVENT_BUGNOTE_ADD_FORM' => 'bugnote_add_form',
    );
  }

  public function bugnote_add_form($event, $bug_id) {
    // if (substr($_SERVER['PHP_SELF'], -(strlen('/bug_update_page.php'))) !== '/bug_update_page.php'
    // && substr($_SERVER['PHP_SELF'], -(strlen('/bug_change_status_page.php'))) !== '/bug_change_status_page.php') {
    //   return;
    // }

    echo '<tr>';
    echo '<th class="category">Mattermost</th>';
    echo '<td colspan="5">';
    echo '<label>';
    echo '<input ', helper_get_tab_index(), ' name="mattermost_skip" class="ace" type="checkbox" />';
    echo '<span class="lbl"> ' . plugin_lang_get('skip') . '</span>';
    echo '</label>';
    echo '</td></tr>';
  }

  public function bug_report_data($event, $bug) {
    $this->oldBug = false;
    return $bug;
  }

  public function bug_update_data($event, $newBug, $oldBug) {
    $this->oldBug = $oldBug;
    return $newBug;
  }

  public function bug_report_update($event, $bug, $bug_id) {
    // BugData Object
    // (
    //   [id:protected] => 5462
    //   [project_id:protected] => 19
    //   [reporter_id:protected] => 19
    //   [handler_id:protected] => 22
    //   [duplicate_id:protected] => 0
    //   [priority:protected] => 30
    //   [severity:protected] => 50
    //   [reproducibility:protected] => 70
    //   [status:protected] => 10
    //   [resolution:protected] => 10
    //   [projection:protected] => 10
    //   [category_id:protected] => 149
    //   [date_submitted:protected] => 1515664427
    //   [last_updated:protected] => 1555397680
    //   [eta:protected] => 10
    //   [os:protected] =>
    //   [os_build:protected] =>
    //   [platform:protected] =>
    //   [version:protected] =>
    //   [fixed_in_version:protected] =>
    //   [target_version:protected] =>
    //   [build:protected] =>
    //   [view_state:protected] => 10
    //   [summary:protected] => CUBE: Text von Anmerkungen lässt sich nicht markieren um diesen per copy/paste weiter zu verwenden
    //   [sponsorship_total:protected] => 0
    //   [sticky:protected] => 0
    //   [due_date:protected] => 1
    //   [profile_id:protected] => 0
    //   [description:protected] => Text von Anmerkungen lässt sich nicht markieren um diesen per copy/paste weiter zu verwenden
    //   [steps_to_reproduce:protected] =>
    //   [additional_information:protected] =>
    //   [_stats:BugData:private] =>
    //   [attachment_count] =>
    //   [bugnotes_count] =>
    //   [loading:BugData:private] =>
    //   [bug_text_id] => 5462
    // )
    // BugnoteData Object
    // (
    //   [id] => 3590
    //   [bug_id] => 5457
    //   [reporter_id] => 2
    //   [note] => Testen 123
    //   [view_state] => 10
    //   [date_submitted] => 1555402312
    //   [last_modified] => 1555402312
    //   [note_type] => 0
    //   [note_attr] =>
    //   [time_tracking] => 0
    //   [bugnote_text_id] => 3590
    // )

    $this->skip = $this->skip || gpc_get_bool('mattermost_skip') || $bug->view_state == VS_PRIVATE;

    if($this->skip)
      return;

    $project = project_get_name($bug->project_id);
    $url = string_get_bug_view_url_with_fqdn($bug_id);
    $summary = $this->format_summary($bug);
    $current = $this->get_user_name(auth_get_current_user_id());
    $reporter = $bug->reporter_id > 0 ? $this->get_user_name($bug->reporter_id) : $this->get_default_channel($bug->project_id);
    $handler = $bug->handler_id > 0 ? $this->get_user_name($bug->handler_id) : $this->get_default_channel($bug->project_id);
    $channel = false;
    $msg = false;
    $mention_msg = false;
    $bugnotes = bugnote_get_all_bugnotes($bug_id);
    $bugnote = false;

    if (count($bugnotes) > 0) {
      $bugnote = $bugnotes[count($bugnotes) - 1];

      if($bugnote->last_modified < $bug->last_updated - 1 || $bugnote->reporter_id !== auth_get_current_user_id())
        $bugnote = false;
    }

    if($this->oldBug && $this->oldBug->status == $bug->status && $this->oldBug->handler_id == $bug->handler_id) {
      if($this->oldBug->summary != $bug->summary
      || $this->oldBug->priority != $bug->priority
      || $this->oldBug->severity != $bug->severity
      || $this->oldBug->resolution != $bug->resolution
      || $this->oldBug->projection != $bug->projection) {
        switch ($bug->status) {
          case 10: // Neu erstellt
          case 30: // Anerkannt
          case 50: // Zugeordnet
          case 85: // Freigegeben
          case 87: // Abgelehnt
            if($bug->handler_id !== auth_get_current_user_id()) {
              if ($bug->handler_id > 0)
                $channel = $handler;
              else
                $channel = $this->get_default_channel($bug->project_id);
            }

            break;

          case 20: // Rückmeldung
          case 80: // Gelöst
          case 90: // Geschlossen
            if($bug->reporter_id !== auth_get_current_user_id()) {
              if ($bug->reporter_id > 0)
                $channel = $reporter;
            }

            break;
        }

        $msg = sprintf('[%s] %s hat den Eintrag <%s|%s> bearbeitet', $project, $current, $url, $summary);
      }
      else if($bugnote)
      {
        $this->oldBug = false;
        $this->bugnote_add_edit('EVENT_BUGNOTE_ADD', $bug->id, $bugnote->id);
        return;
      }
    }

    if(!$msg) {
      switch ($bug->status) {
        case 10: // Neu erstellt
          if($bug->handler_id !== auth_get_current_user_id()) {
            if ($bug->handler_id > 0)
              $channel = $handler;
            else
            {
              $channel = $this->get_default_channel($bug->project_id);

              // Fängt den Fall ab, dass der Standardkanal für dieses Projekt dem aktuellen Benutzer entspricht:
              if($channel == $current)
                $channel = false;
            }

            $msg = sprintf('[%s] %s hat den Eintrag <%s|%s> erstellt', $project, $reporter, $url, $summary);
          }

          break;

        case 20: // Rückmeldung
          if ($bug->handler_id > 0 && $bug->handler_id !== auth_get_current_user_id()) {
            $channel = $handler;
            $msg = sprintf('[%s] %s hat deinen Eintrag <%s|%s> auf "Rückmeldung" gesetzt', $project, $current, $url, $summary);
            $mention_msg = sprintf('[%s] %s hat den von %s erstellten Eintrag <%s|%s> auf "Rückmeldung" gesetzt', $project, $current, $reporter, $url, $summary);
          }
          else {
            $channel = $current;
            $msg = sprintf('[%s] Du hast den Eintrag <%s|%s> auf "Rückmeldung" gesetzt, dabei aber keinen (neuen) Bearbeiter festgelegt', $project, $url, $summary);
            $bugnote = false;
          }

          break;

        case 30: // Anerkannt
          if ($bug->reporter_id > 0 && $bug->reporter_id !== auth_get_current_user_id()) {
            $channel = $reporter;
            $msg = sprintf('[%s] %s hat deinen Eintrag <%s|%s> anerkannt', $project, $current, $url, $summary);
            $mention_msg = sprintf('[%s] %s hat den von %s erstellten Eintrag <%s|%s> von %s', $project, $current, $reporter, $url, $summary);
          }

          break;

        case 50: // Zugeordnet
          if ($bug->handler_id > 0 && $bug->handler_id !== auth_get_current_user_id()) {
            $channel = $handler;
            $msg = sprintf('[%s] %s hat dir den Eintrag <%s|%s> zugeordnet', $project, $current, $url, $summary);
            $mention_msg = sprintf('[%s] %s hat den von %s erstellten Eintrag <%s|%s> an %s zugeordnet', $project, $current, $reporter, $url, $summary, $handler);
          }
          else if ($bug->handler_id == 0) {
            $channel = $current;
            $msg = sprintf('[%s] Du hast den Eintrag <%s|%s> auf "Zugeordnet" gesetzt, dabei aber keinen Bearbeiter festgelegt', $project, $url, $summary);
            $bugnote = false;
          }

          break;

        case 80: // Gelöst
          if ($bug->reporter_id > 0 && $bug->reporter_id !== auth_get_current_user_id()) {
            $channel = $reporter;

            $msg = sprintf('[%s] %s hat deinen Eintrag <%s|%s> gelöst', $project, $current, $url, $summary);
            $mention_msg = sprintf('[%s] %s hat den von %s erstellten Eintrag <%s|%s> gelöst', $project, $current, $reporter, $url, $summary);
          }

          break;

        case 85: // Freigegeben
          if ($bug->handler_id > 0 && $bug->handler_id !== auth_get_current_user_id()) {
            $channel = $handler;
            $msg = sprintf('[%s] %s hat den von dir gelösten Eintrag <%s|%s> freigegeben', $project, $current, $url, $summary);
            $mention_msg = sprintf('[%s] %s hat den von %s gelösten Eintrag <%s|%s> freigegeben', $project, $current, $handler, $url, $summary);
          }

          break;

        case 87: // Abgelehnt
          if ($bug->handler_id > 0 && $bug->handler_id !== auth_get_current_user_id()) {
            $channel = $handler;
            $msg = sprintf('[%s] %s hat den von dir gelösten Eintrag <%s|%s> *abgelehnt*', $project, $current, $url, $summary);
            $mention_msg = sprintf('[%s] %s hat den von %s gelösten Eintrag <%s|%s> *abgelehnt*', $project, $current, $handler, $url, $summary);
          }

          break;

        case 90: // Geschlossen
          if ($bug->reporter_id > 0 && $bug->reporter_id !== auth_get_current_user_id()) {
            $channel = $reporter;
            $msg = sprintf('[%s] %s hat den von dir erstellten Eintrag <%s|%s> *geschlossen*', $project, $current, $url, $summary);
            $mention_msg = sprintf('[%s] %s hat den von %s erstellten Eintrag <%s|%s> *geschlossen*', $project, $current, $reporter, $url, $summary);
          }

          break;
      }
    }

    $attachments = array($this->get_attachment($bug));

    if($bugnote) {
      $msg .= ' und dazu folgende Nachricht hinterlassen:' . "\n" . $this->quote_text($this->bbcode_to_mattermost($bugnote->note));

      if($mention_msg)
        $mention_msg .= ' und dazu folgende Nachricht hinterlassen:' . "\n" . $this->quote_text($this->bbcode_to_mattermost($bugnote->note));
    }
    else {
      $msg .= '.';

      if($mention_msg)
        $mention_msg .= '.';
    }

    if($bugnote)
    {
      $mentions = $this->get_mentions($bugnote->note);

      if(count($mentions) > 0)
      {
        for($i = 0; $i < count($mentions); $i++) {
          $mention_channel = $this->get_translate_user_name($mentions[$i]);

          if($mention_channel != $channel) {
            $this->notify($mention_msg ? $mention_msg : $msg, $this->get_webhook(), $mention_channel, $attachments);

            // Wenn diese Notiz direkt an einen Benutzer gerichtet war, benachrichtigen wir den Standardkanal nicht mehr.
            if($channel == $this->get_default_channel($bug->project_id))
              $channel = false;
          }
        }
      }
    }

    $this->notify($msg, $this->get_webhook(), $channel, $attachments);
    $this->skip = true; // Folgende Events auf jeden Fall blockieren
  }

  public function bug_report($event, $bug, $bug_id) {
    $this->bug_report_update($event, $bug, $bug_id);
  }

  public function bug_update($event, $bug_existing, $bug_updated) {
    $this->bug_report_update($event, $bug_updated, $bug_updated->id);
  }

  public function bug_action($event, $action, $bug_id) {
    $this->skip = $this->skip || gpc_get_bool('mattermost_skip') || plugin_config_get('skip_bulk');

    if($this->skip)
      return;

    if ($action !== 'DELETE') {
      $bug = bug_get($bug_id);
      $this->bug_report_update('EVENT_UPDATE_BUG', $bug, $bug_id);
    }
  }

  public function bug_deleted($event, $bug_id) {
    $bug = bug_get($bug_id);

    $this->skip = $this->skip || gpc_get_bool('mattermost_skip') || $bug->view_state == VS_PRIVATE;

    if($this->skip)
      return;

    $project = project_get_name($bug->project_id);
    $url = string_get_bug_view_url_with_fqdn($bug_id);
    $summary = $this->format_summary($bug);
    $current = $this->get_user_name(auth_get_current_user_id());
    $reporter = $bug->reporter_id > 0 ? $this->get_user_name($bug->reporter_id) : $this->get_default_channel($bug->project_id);
    $handler = $bug->handler_id > 0 ? $this->get_user_name($bug->handler_id) : $this->get_default_channel($bug->project_id);
    $channel = false;
    $msg = false;

    if($bug->handler_id > 0 && $bug->handler_id !== auth_get_current_user_id()) {
      $channel = $handler;
      $msg = sprintf('[%s] %s hat den Eintrag <%s|%s> gelöscht, welchem du als Bearbeiter zugewiesen warst.', $project, $current, $url, $summary);
    }
    else {
      $channel = $this->get_default_channel($bug->project_id);

      // Fängt den Fall ab, dass der Standardkanal für dieses Projekt dem aktuellen Benutzer entspricht:
      if($channel == $current)
        $channel = false;

      $msg = sprintf('[%s] %s hat den Eintrag <%s|%s> gelöscht.', $project, $current, $url, $summary);
    }

    $this->notify($msg, $this->get_webhook(), $channel);

    if($bug->reporter_id != $bug->handler_id && $bug->reporter_id !== auth_get_current_user_id()) {
      $channel = $reporter;
      $msg = sprintf('[%s] %s hat deinen Eintrag <%s|%s> gelöscht.', $project, $current, $url, $summary);
      $this->notify($msg, $this->get_webhook(), $channel, $this->get_attachment($bug));
    }
  }

  public function bugnote_add_edit($event, $bug_id, $bugnote_id) {
    // Wenn vorher das event EVENT_UPDATE_BUG_DATA aufgerufen wurde, befinden wir uns auf einer
    // kombinierten Seite (z.B. bug_change_status_page.php) - Da dann auch das Event EVENT_UPDATE_BUG
    // aufgerufen wird, ignorieren wir hier das Event (ansonsten erfolgen zwei Benachrichtigungen).
    if($this->oldBug)
      return;

    $bug = bug_get($bug_id);
    $bugnote = bugnote_get($bugnote_id);

    $this->skip = $this->skip || gpc_get_bool('mattermost_skip') || $bug->view_state == VS_PRIVATE || $bugnote->view_state == VS_PRIVATE;

    if($this->skip)
      return;

    $project = project_get_name($bug->project_id);
    $url = string_get_bug_view_url_with_fqdn($bug_id, $bugnote_id);
    $summary = $this->format_summary($bug);
    $current = $this->get_user_name(auth_get_current_user_id());
    $reporter = $bug->reporter_id > 0 ? $this->get_user_name($bug->reporter_id) : $this->get_default_channel($bug->project_id);
    $handler = $bug->handler_id > 0 ? $this->get_user_name($bug->handler_id) : $this->get_default_channel($bug->project_id);
    $channel = false;
    $msg = false;

    switch ($bug->status) {
      case 10: // Neu erstellt
      case 30: // Anerkannt
      case 85: // Freigegeben
      case 87: // Abgelehnt
        if($bug->handler_id !== auth_get_current_user_id()) {
          if ($bug->handler_id > 0)
            $channel = $handler;
          else {
            $channel = $this->get_default_channel($bug->project_id);

            // Fängt den Fall ab, dass der Standardkanal für dieses Projekt dem aktuellen Benutzer entspricht:
            if($channel == $current)
              $channel = false;
          }
        }

        break;

      case 20: // Rückmeldung
        if ($bug->handler_id == 0) {
          $channel = $current;
          $msg = sprintf('[%s] Du hast dem auf "Rückmeldung" gesetzten Eintrag <%s|%s> eine Nachricht hinzugefügt, obwohl kein Bearbeiter festgelegt war.', $project, $url, $summary);
        }
        else if($bug->handler_id !== auth_get_current_user_id())
          $channel = $handler;

        break;

      case 50: // Zugeordnet
        if ($bug->handler_id == 0) {
          $channel = $current;
          $msg = sprintf('[%s] Du hast einem zugeordneten Eintrag <%s|%s> eine Nachricht hinzugefügt, obwohl kein Bearbeiter festgelegt war.', $project, $url, $summary);
        }
        else
          $channel = $handler;

        break;

      case 80: // Gelöst
      case 90: // Geschlossen
        if($bug->reporter_id !== auth_get_current_user_id()) {
          if ($bug->reporter_id > 0)
            $channel = $reporter;
        }

        break;
    }

    if(!$msg)
      $msg = sprintf('[%s] %s hat eine Nachricht für den Eintrag <%s|%s> hinterlassen:', $project, $current, $url, $summary);

    $mention_msg = sprintf('[%s] %s hat eine Nachricht für den Eintrag <%s|%s> hinterlassen:', $project, $current, $url, $summary);
    $mentions = $this->get_mentions($bugnote->note);

    if(count($mentions) > 0)
    {
      $mention_msg .= "\n" . $this->quote_text($this->bbcode_to_mattermost($bugnote->note));

      for($i = 0; $i < count($mentions); $i++) {
        $mention_channel = $this->get_translate_user_name($mentions[$i]);

        if($mention_channel != $channel) {
          $this->notify($mention_msg, $this->get_webhook(), $mention_channel, array($this->get_attachment($bug)));

          // Wenn diese Notiz direkt an einen Benutzer gerichtet war, benachrichtigen wir den Standardkanal nicht mehr.
          if($channel == $this->get_default_channel($bug->project_id))
            $channel = false;
        }
      }
    }

    $msg .= "\n" . $this->quote_text($this->bbcode_to_mattermost($bugnote->note));
    $this->notify($msg, $this->get_webhook(), $channel, array($this->get_attachment($bug)));
  }

  public function get_mentions($text) {
    $matches = array();
    preg_match_all('/@[A-z0-9_-]+/', $text, $matches);
    return $matches[0];
  }

  public function get_text_attachment($text) {
    $attachment = array('color' => '#3AA3E3', 'mrkdwn_in' => array('pretext', 'text', 'fields'));
    $attachment['fallback'] = $text . "\n";
    $attachment['text'] = $text;
    return $attachment;
  }

  public function format_summary($bug) {
    $summary = bug_format_id($bug->id) . ': ' . string_display_line_links($bug->summary);
    return strip_tags(html_entity_decode($summary));
  }

  public function format_text($bug, $text) {
    $t = string_display_line_links($this->bbcode_to_mattermost($text));
    return strip_tags(html_entity_decode($t));
  }

  public function get_attachment($bug) {
    $attachment = array('text' => '', 'color' => '#3AA3E3', 'mrkdwn_in' => array('pretext', 'text', 'fields'));

    global $g_status_colors;

    switch ($bug->status) {
      case 10: // Neu erstellt
        if($g_status_colors['new'])
          $attachment['color'] = $g_status_colors['new'];

        break;

      case 20: // Rückmeldung
        if($g_status_colors['feedback'])
          $attachment['color'] = $g_status_colors['feedback'];

        break;

      case 30: // Anerkannt
        if($g_status_colors['acknowledged'])
          $attachment['color'] = $g_status_colors['acknowledged'];

        break;

      case 50: // Zugeordnet
        if($g_status_colors['assigned'])
          $attachment['color'] = $g_status_colors['assigned'];

        break;

      case 80: // Gelöst
        if($g_status_colors['resolved'])
          $attachment['color'] = $g_status_colors['resolved'];

        break;

      case 85: // Freigegeben
        if($g_status_colors['tested'])
          $attachment['color'] = $g_status_colors['tested'];

        break;

      case 87: // Abgelehnt
        if($g_status_colors['rejected'])
          $attachment['color'] = $g_status_colors['rejected'];

        break;

      case 90: // Geschlossen
        if($g_status_colors['closed'])
          $attachment['color'] = $g_status_colors['closed'];

        break;
    }

    $t_columns = (array) plugin_config_get('columns');
    $values = array();
    foreach ($t_columns as $t_column) {
      $title = column_get_title($t_column);
      $value = $this->format_value($bug, $t_column);

      if ($title && $value) {
        $values[] = '**' . $title . ':** ' . $value;
        // $attachment['fields'][] = array(
        //   'title' => $title,
        //   'value' => $value,
        //   'short' => !column_is_extended($t_column),
        // );
      }
    }

    $attachment['text'] = implode(', ', $values);
    return $attachment;
  }

  public function format_value($bug, $field_name) {
    $self = $this;
    $values = array(
      'id' => function ($bug) {
        return sprintf('<%s|%s>', string_get_bug_view_url_with_fqdn($bug->id), $bug->id);
      },
      'project_id' => function ($bug) {
        return project_get_name($bug->project_id);
      },
      'reporter_id' => function ($bug) {
        return $this->get_user_name($bug->reporter_id);
      },
      'handler_id' => function ($bug) {
        return empty($bug->handler_id) ? plugin_lang_get('no_user') : $this->get_user_name($bug->handler_id);
      },
      'duplicate_id' => function ($bug) {
        return sprintf('<%s|%s>', string_get_bug_view_url_with_fqdn($bug->duplicate_id), $bug->duplicate_id);
      },
      'priority' => function ($bug) {
        return get_enum_element('priority', $bug->priority);
      },
      'severity' => function ($bug) {
        return get_enum_element('severity', $bug->severity);
      },
      'reproducibility' => function ($bug) {
        return get_enum_element('reproducibility', $bug->reproducibility);
      },
      'status' => function ($bug) {
        return get_enum_element('status', $bug->status);
      },
      'resolution' => function ($bug) {
        return get_enum_element('resolution', $bug->resolution);
      },
      'projection' => function ($bug) {
        return get_enum_element('projection', $bug->projection);
      },
      'category_id' => function ($bug) {
        return category_full_name($bug->category_id, false);
      },
      'eta' => function ($bug) {
        return get_enum_element('eta', $bug->eta);
      },
      'view_state' => function ($bug) {
        return $bug->view_state == VS_PRIVATE ? lang_get('private') : lang_get('public');
      },
      'sponsorship_total' => function ($bug) {
        return sponsorship_format_amount($bug->sponsorship_total);
      },
      'os' => function ($bug) {
        return $bug->os;
      },
      'os_build' => function ($bug) {
        return $bug->os_build;
      },
      'platform' => function ($bug) {
        return $bug->platform;
      },
      'version' => function ($bug) {
        return $bug->version;
      },
      'fixed_in_version' => function ($bug) {
        return $bug->fixed_in_version;
      },
      'target_version' => function ($bug) {
        return $bug->target_version;
      },
      'build' => function ($bug) {
        return $bug->build;
      },
      'summary' => function ($bug) use ($self) {
        return $self->format_summary($bug);
      },
      'last_updated' => function ($bug) {
        return date(config_get('short_date_format'), $bug->last_updated);
      },
      'date_submitted' => function ($bug) {
        return date(config_get('short_date_format'), $bug->date_submitted);
      },
      'due_date' => function ($bug) {
        return date(config_get('short_date_format'), $bug->due_date);
      },
      'description' => function ($bug) use ($self) {
        return $self->format_text($bug, $bug->description);
      },
      'steps_to_reproduce' => function ($bug) use ($self) {
        return $self->format_text($bug, $bug->steps_to_reproduce);
      },
      'additional_information' => function ($bug) use ($self) {
        return $self->format_text($bug, $bug->additional_information);
      },
    );
    // Discover custom fields.
    $t_related_custom_field_ids = custom_field_get_linked_ids($bug->project_id);
    foreach ($t_related_custom_field_ids as $t_id) {
      $t_def = custom_field_get_definition($t_id);
      $values['custom_' . $t_def['name']] = function ($bug) use ($t_id) {
        return custom_field_get_value($t_id, $bug->id);
      };
    }
    if (isset($values[$field_name])) {
      $func = $values[$field_name];
      return $func($bug);
    }
    else {
      return false;
    }
  }

  public function get_default_channel($project_id) {
    require_api('project_hierarchy_api.php');

    $global_channel = plugin_config_get('default_channel', null, false, null, ALL_PROJECTS);

    do
    {
      $project_channel = plugin_config_get('default_channel', null, false, null, $project_id);
      $project_id = project_hierarchy_get_parent($project_id);
    } while($project_channel == $global_channel && $project_id > ALL_PROJECTS);

    return $project_channel;
  }

  public function get_webhook() {
    return plugin_config_get('url_webhook');
  }

  public function notify($msg, $webhook, $channel, $attachments = false) {
    if($this->skip || empty($msg) || empty($webhook) || empty($channel))
      return;

    $url = sprintf('%s', trim($webhook));

    $payload = array(
      'channel' => $channel,
      'username' => plugin_config_get('bot_name'),
      'text' => $msg,
      'link_names' => plugin_config_get('link_names'),
    );

    $bot_icon = trim(plugin_config_get('bot_icon'));

    if (empty($bot_icon)) {
      $payload['icon_url'] = 'https://github.com/aalasolutions/MantisBT-Mattermost/blob/master/mantis_logo.png?raw=true';
    }
    elseif (preg_match('/^:[a-z0-9_\-]+:$/i', $bot_icon)) {
      $payload['icon_emoji'] = $bot_icon;
    }
    elseif ($bot_icon) {
      $payload['icon_url'] = trim($bot_icon);
    }

    if ($attachments && is_array($attachments) && count($attachments) > 0) {
      $payload['attachments'] = $attachments;
    }
    else if($attachments && !is_array($attachments)) {
      $payload['attachments'] = array($attachments);
    }

    $data = json_encode($payload);
// echo '<pre>';
// print_r($payload);
// echo '</pre>';
// exit;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    if ($result !== 'ok') {
      trigger_error(curl_errno($ch) . ': ' . curl_error($ch), E_USER_WARNING);
      plugin_error('ERROR_CURL', E_USER_ERROR);
    }
    curl_close($ch);
  }

  public function bbcode_to_mattermost($bbtext) {
    $bbtags = array(
      '[b]' => '**',
      '[/b]' => '** ',
      '[i]' => '*',
      '[/i]' => '* ',
      '[u]' => '_',
      '[/u]' => '_ ',
      '[s]' => '~',
      '[/s]' => '~ ',
      '[sup]' => '',
      '[/sup]' => '',
      '[sub]' => '',
      '[/sub]' => '',

      '[list]' => '',
      '[/list]' => "\n",
      '[*]' => '• ',

      '[hr]' => "\n———\n",

      '[left]' => '',
      '[/left]' => '',
      '[right]' => '',
      '[/right]' => '',
      '[center]' => '',
      '[/center]' => '',
      '[justify]' => '',
      '[/justify]' => '',
    );

    $bbtext = str_ireplace(array_keys($bbtags), array_values($bbtags), $bbtext);

    $bbextended = array(
      "/\[code(.*?)\](.*?)\[\/code\]/is" => "```$2```",
      "/\[color(.*?)\](.*?)\[\/color\]/is" => "$2",
      "/\[size=(.*?)\](.*?)\[\/size\]/is" => "$2",
      "/\[highlight(.*?)\](.*?)\[\/highlight\]/is" => "$2",
      "/\[url](.*?)\[\/url]/i" => "<$1>",
      "/\[url=(.*?)\](.*?)\[\/url\]/i" => "<$1|$2>",
      "/\[email=(.*?)\](.*?)\[\/email\]/i" => "<mailto:$1|$2>",
      "/\[img\]([^[]*)\[\/img\]/i" => "<$1>",
    );

    foreach ($bbextended as $match => $replacement) {
      $bbtext = preg_replace($match, $replacement, $bbtext);
    }
    $bbtext = preg_replace_callback("/\[quote(=)?(.*?)\](.*?)\[\/quote\]/is", function ($matches) {
      if (!empty($matches[2])) {
        $result = "\n> _*" . $matches[2] . "* schrieb:_\n> \n";
      }
      $lines = explode("\n", $matches[3]);
      foreach ($lines as $line) {
        $result .= "> " . $line . "\n";
      }
      return $result;
    }, $bbtext);
    return $bbtext;
  }

  public function get_user_name($user_id) {
    $user = user_get_row($user_id);
    $username = $user['username'];
    $usernames = (array) plugin_config_get('usernames');
    $username = array_key_exists($username, $usernames) ? $usernames[$username] : $username;
    return '@' . $username;
  }

  public function get_translate_user_name($mantis_user_name) {
    $usernames = (array) plugin_config_get('usernames');
    $username = array_key_exists($mantis_user_name, $usernames) ? $usernames[$mantis_user_name] : $mantis_user_name;
    return $username;
  }

  public function quote_text($text) {
    $text = trim($text);

    return $text ? '> ' . preg_replace('/\r\n|\r|\n/', '$0> ', $text) : '';
  }
}
