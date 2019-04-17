MantisBT-Mattermost
==============

A [MantisBT](http://www.mantisbt.org/) plugin to send bug updates to [Mattermost](https://about.mattermost.com/)
channels.

It is only for Mantis 2.0.x, not backward compatible.

In contrast to [MantisBT-Mattermost](https://github.com/aalasolutions/MantisBT-Mattermost) this fork treats entries rather on the basis of the users who have entered or been assigned the entry. This leads to less irrelevant notifications being sent to whole channels. For new entries there is the possibility to define a standard channel or user for each project (hierarchical).

The project currently contains some adjustments to our company's own status. Create a fork to make the appropriate changes for your needs.

Currently not all texts are translated (they are in German). Maybe this will be fixed soon.


# Setup
1. Extract this repo to your *Mantis folder/plugins/Mattermost*.
2. On Mattermost, add a new "Incoming Webhooks" for your channel and note the URL that Mattermost generates
for you.
3. On the MantisBT side, access the plugin's configuration page and fill in your Mattermost webhook URL.
4. You can map your MantisBT projects to Mattermost channels by setting the *plugin_Matter_channels* option in
Mantis.
5. Follow the instructions on the plugin's configuration page to get there. You can define default project channels
via Option *plugin_Mattermost_default_channel*. Define this property for every project, where the default channel
should differ from "All projects".
6. You can specify which bug fields appear in the Matter notifications. Edit the *plugin_Matter_columns* configuration
option for this purpose.  Follow the instructions on the plugin configuration page.


_Heavily inspired by [MantisBT-Slack](https://github.com/infojunkie/MantisBT-Slack) Plugin._
_Forked from [MantisBT-Mattermost](https://github.com/aalasolutions/MantisBT-Mattermost)_

Thank you for your work!