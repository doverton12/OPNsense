# OPNsense Speedtest Graph
This items only works once you have installed the excellent speedtest widget from Miha Kralj (https://github.com/mihakralj/opnsense-speedtest on GitHub,  mihak09@gmail.com as an e-mail, Info about the new repo at https://forum.opnsense.org/index.php?topic=20827.0 and more information at his we site - https://www.routerperformance.net/opnsense-repo/)

I've not created a plugin yet, so for now you will need to install by hand from the shell.

## install Speedtest Graph widget
- download SpeedtestGraph.widget.php ➡ /usr/local/www/widgets/widgets
- download SpeedtestGraph.inc ➡ /usr/local/www/widgets/include

You will then see the widget that you can add in the Dashboard.

## Features
- connects to your existing Speedtest output as provided bny mimugmail
- shows uplaod, download and latency by default.
- clicking on the items in the chart legend will hide them. These settings are remembered in your browser for every reload
- graph size can be changed in the settings
- refresh frequency can be set in the settings
