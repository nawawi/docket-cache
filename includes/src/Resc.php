<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

final class Resc
{
    public static function iconmenu()
    {
        $icon = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+Cjxz';
        $icon .= 'dmcKICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICB4bWxu';
        $icon .= 'czpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIgogICB4bWxuczpyZGY9Imh0dHA6';
        $icon .= 'Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiCiAgIHhtbG5zOnN2Zz0iaHR0';
        $icon .= 'cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAw';
        $icon .= 'L3N2ZyIKICAgeG1sbnM6c29kaXBvZGk9Imh0dHA6Ly9zb2RpcG9kaS5zb3VyY2Vmb3JnZS5uZXQv';
        $icon .= 'RFREL3NvZGlwb2RpLTAuZHRkIgogICB4bWxuczppbmtzY2FwZT0iaHR0cDovL3d3dy5pbmtzY2Fw';
        $icon .= 'ZS5vcmcvbmFtZXNwYWNlcy9pbmtzY2FwZSIKICAgd2lkdGg9IjEyOCIKICAgaGVpZ2h0PSIxMjgi';
        $icon .= 'CiAgIHZpZXdCb3g9IjAgMCAxMjggMTI4IgogICB2ZXJzaW9uPSIxLjEiCiAgIGlkPSJzdmcxMSIK';
        $icon .= 'ICAgc29kaXBvZGk6ZG9jbmFtZT0iZG9ja2V0LWNhY2hlLWxvZ28tY3V0LnN2ZyIKICAgaW5rc2Nh';
        $icon .= 'cGU6dmVyc2lvbj0iMS4wLjEgKDNiYzJlODEzZjUsIDIwMjAtMDktMDcpIj4KICA8bWV0YWRhdGEK';
        $icon .= 'ICAgICBpZD0ibWV0YWRhdGExNSI+CiAgICA8cmRmOlJERj4KICAgICAgPGNjOldvcmsKICAgICAg';
        $icon .= 'ICAgcmRmOmFib3V0PSIiPgogICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2Uvc3ZnK3htbDwvZGM6Zm9y';
        $icon .= 'bWF0PgogICAgICAgIDxkYzp0eXBlCiAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwOi8vcHVy';
        $icon .= 'bC5vcmcvZGMvZGNtaXR5cGUvU3RpbGxJbWFnZSIgLz4KICAgICAgICA8ZGM6dGl0bGU+PC9kYzp0';
        $icon .= 'aXRsZT4KICAgICAgPC9jYzpXb3JrPgogICAgPC9yZGY6UkRGPgogIDwvbWV0YWRhdGE+CiAgPHNv';
        $icon .= 'ZGlwb2RpOm5hbWVkdmlldwogICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIKICAgICBib3JkZXJjb2xv';
        $icon .= 'cj0iIzY2NjY2NiIKICAgICBib3JkZXJvcGFjaXR5PSIxIgogICAgIG9iamVjdHRvbGVyYW5jZT0i';
        $icon .= 'MTAiCiAgICAgZ3JpZHRvbGVyYW5jZT0iMTAiCiAgICAgZ3VpZGV0b2xlcmFuY2U9IjEwIgogICAg';
        $icon .= 'IGlua3NjYXBlOnBhZ2VvcGFjaXR5PSIwIgogICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiCiAg';
        $icon .= 'ICAgaW5rc2NhcGU6d2luZG93LXdpZHRoPSIxOTIwIgogICAgIGlua3NjYXBlOndpbmRvdy1oZWln';
        $icon .= 'aHQ9IjEwMDEiCiAgICAgaWQ9Im5hbWVkdmlldzEzIgogICAgIHNob3dncmlkPSJmYWxzZSIKICAg';
        $icon .= 'ICBpbmtzY2FwZTp6b29tPSI2LjQ4NDM3NSIKICAgICBpbmtzY2FwZTpjeD0iNDUuMzM5NzU5Igog';
        $icon .= 'ICAgIGlua3NjYXBlOmN5PSI1Ny44MzEzMjUiCiAgICAgaW5rc2NhcGU6d2luZG93LXg9Ii05Igog';
        $icon .= 'ICAgIGlua3NjYXBlOndpbmRvdy15PSItOSIKICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVk';
        $icon .= 'PSIxIgogICAgIGlua3NjYXBlOmN1cnJlbnQtbGF5ZXI9ImRvY2tldC1jYWNoZS1sb2dvIiAvPgog';
        $icon .= 'IDxkZWZzCiAgICAgaWQ9ImRlZnM1Ij4KICAgIDxjbGlwUGF0aAogICAgICAgaWQ9ImNsaXAtZG9j';
        $icon .= 'a2V0LWNhY2hlLWxvZ28iPgogICAgICA8cmVjdAogICAgICAgICB3aWR0aD0iMTI4IgogICAgICAg';
        $icon .= 'ICBoZWlnaHQ9IjEyOCIKICAgICAgICAgaWQ9InJlY3QyIiAvPgogICAgPC9jbGlwUGF0aD4KICA8';
        $icon .= 'L2RlZnM+CiAgPGcKICAgICBpZD0iZG9ja2V0LWNhY2hlLWxvZ28iCiAgICAgY2xpcC1wYXRoPSJ1';
        $icon .= 'cmwoI2NsaXAtZG9ja2V0LWNhY2hlLWxvZ28pIj4KICAgIDxyZWN0CiAgICAgICBzdHlsZT0iZmls';
        $icon .= 'bDojMDAwMGZmO2ZpbGwtcnVsZTpldmVub2RkIgogICAgICAgaWQ9InJlY3Q5NCIKICAgICAgIHdp';
        $icon .= 'ZHRoPSIzNC42OTg3OTUiCiAgICAgICBoZWlnaHQ9IjI3LjYwNDgxOCIKICAgICAgIHg9Ii01NS4w';
        $icon .= 'NTU0MiIKICAgICAgIHk9Ii0yLjAwNDgxOTQiIC8+CiAgICA8cGF0aAogICAgICAgaWQ9IkVsbGlw';
        $icon .= 'c2VfMSIKICAgICAgIHN0eWxlPSJmaWxsOiNiYWJhYmEiCiAgICAgICBkPSJNIDYzLjcwMzEyNSwx';
        $icon .= 'LjcyMTM4NDVlLTQgQSA2NCw2NCAwIDAgMCAzLjM1MDYwMjVlLTcsNjQuMDAwMTcyIDY0LDY0IDAg';
        $icon .= 'MCAwIDY0LDEyOC4wMDAxOCA2NCw2NCAwIDAgMCAxMjgsNjQuMDAwMTcyIDY0LDY0IDAgMCAwIDY0';
        $icon .= 'LDEuNzIxMzg0NWUtNCBhIDY0LDY0IDAgMCAwIC0wLjI5Njg3NSwwIHogTSAzMy44NTc0MjIsMjAu';
        $icon .= 'MjczNjEgYyAwLDAgNTEuMTA0MzEyLDM5LjgyOTQ5OCA1NS4xOTUzMDIsNTYuMTg3NSBhIDE2LjQ3';
        $icon .= 'OCwxNi40NzggMCAwIDEgMC43NjU2Myw0LjI5ODgyOCBjIDAuNDI2LDUuMjg0IC0zLjQ1MDM3LDE1';
        $icon .= 'LjYzNTU5NSAtMTcuMzU5MzcsMTcuNjgzNTk1IC0yLjQ4MSwwLjUzOTAwNCAtOS4zODA4NTksMCAt';
        $icon .= 'OS4zODA4NTksMCAwLDAgOC41MTgxMDksLTEuMjg4ODMgMTEuNTM3MTA2LC0yLjc5ODgzIDMuMDE5';
        $icon .= 'MDAzLC0xLjUxIDEyLjE3NjE3MywtNS44ODc1OCAxMi4wNzYxNzMsLTE0LjAxNzU3OCAtMC4yMTUs';
        $icon .= 'LTMuMjM0IDAuMjE2MTEsLTkuOTIxMzc1IC0zMS44Mzc4ODgsLTIyLjIzNDM3NSAtMTEuMDY1LC00';
        $icon .= 'LjI1IC0yOC4zMjgxMjUsLTkuMTQ0NTMxIC0yOC4zMjgxMjUsLTkuMTQ0NTMxIDAsMCAzMy44NTg0';
        $icon .= 'MDYsMjcuMzg4ODEyIDM1LjY5MTQwNiwzMi4xMzI4MTIgLTkuMTY2LC00Ljc0IC00OS40OTIxODgs';
        $icon .= 'LTM5LjE0MDYyNSAtNDkuNDkyMTg4LC0zOS4xNDA2MjUgYSAzNDYuNywzNDYuNyAwIDAgMSAzOC4y';
        $icon .= 'NzM0MzgsMTEuMTExMzI4IHogbSA5LjE2MjEwOSwxMS4zMTgzNTkgMTIuMjEwOTM4LDI0LjMxODM1';
        $icon .= 'OSBjIDkuMTUzLDMuNDkyIDE4LjE1NTA5Miw3LjYwMTE3MiAyNS4xMjEwOTUsMTIuMjAxMTcyIC0z';
        $icon .= 'LjAzMywtNC4yMTkgLTEzLjE4NDAzMywtMTYuOTc1NTMxIC0zNy4zMzIwMzMsLTM2LjUxOTUzMSB6';
        $icon .= 'IG0gNTcuNjg3NDk5LDM4LjkyMTg3NSA5LjYwMTU2LDE5Ljk0OTIxOSAtOS41NDI5NywtMy45OTAy';
        $icon .= 'MzUgLTExLjY5OTIyNiw1LjA2NjQwNSAxMS42NDA2MzYsLTcuNzYzNjcgNS4zOTI1OCwyLjgwMDc4';
        $icon .= 'MSAtNi40MTQwNjYsLTEzLjA0Njg3NSAtOC40NjY4LDIuOTg0Mzc1IHoiIC8+CiAgPC9nPgo8L3N2';
        $icon .= 'Zz4K';

        $icon = apply_filters('docketcache/filter/resc/iconmenu', $icon);

        return 'data:image/svg+xml;base64,'.$icon;
    }

    public static function iconnav()
    {
        $icon = 'iVBORw0KGgoAAAANSUhEUgAAAG8AAAAfCAYAAADp55OhAAAABHNCSVQICAgIfAhkiAAAAAlwSFlz';
        $icon .= 'AAAOywAADssB4+WgAQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAlJSURB';
        $icon .= 'VGiB7ZtpkFTVFcd/53X3655BxSWJSkxMADfUiD0NowSVqVKrYgVcoHsmQTHlgoZFqUQSg1kgJsYY';
        $icon .= 'tXQKsdypQmRmWiDikrhEUQkVnO4eUcAVk5SKQjA6jg7dr3veyYf72nkMPUvPQL7Y/6qu6XfPueec';
        $icon .= 'e89y77t9RxggWuPhY0SYDHqiCKKII6oRwFEIiCC4vCGWPhZtzm8cqNwKBg/pjyGTCJ6uYs1CdZuo';
        $icon .= 'PvI2hXWJJF09+RQk3RAab6mcp8pRqvJALJl7fN+YXQH04bzWqZGRVsBdJLApq07jhCS7Bio0NZMQ';
        $icon .= 'H4cuE5FJqvwulnRe3TvmVuBHSeelEpE6EfcKtZw5sRXsHKzwzXH2y4rdiPBYTbOzavBmVlAKezgv';
        $icon .= 'Ux+aocrYT3fkf1a3loKCCOhgFShIW8K+zkWysZbczUMzt4JekW4InpZJhHab4EzCnpqaah87VNmp';
        $icon .= 'uH1tJmH/YKhyKuiGVfySqg+PwrVmte/IX+tn6Mw6T0mQNa3x8FlDUVSTdP6oSF06HqoZipwKuvGF';
        $icon .= '80R1USHvzKlbS8HPMHENHaLcZ4km04nQ5YNVJKA5zV0NskAXduutYPCwADLx4BmKvFy7mo9KMQXC';
        $icon .= 'zmLgNUSOTNeHbhvs5E9IsgthdXqz3TAEmyvwYJwg1uyO6tzi3phOWsbnqKwC3SrKK5kt9orUZKoH';
        $icon .= 'ozA6xnlIhGmV7Bs6rEzcHuOKvlu3lGyqIVTbFg+NLTWxwUhuCSoz3QPzywTrHqmyV62Pc3C5CmUh';
        $icon .= 'riLrM5sjk/bKCL7EsBAmi+ifAYa35192LflJZou9M52wH07Vhy7beEHVEWCyT1SStIcujrZkn1HR';
        $icon .= 'BRFCK1MXVB9etlZhNdI1eS+P5UsHS+GErV2F9QBH/YVcTbMzQ5WbBJ0oyphC0F2RSdgbUnH7Whd9';
        $icon .= 'WlQuSc0kFGvOZ7osmSuBwsMvNVR9oxylsebcVkUO3TdD2gP7AfOBuv+TvqFgFnDGQJktgJ5nlbGk';
        $icon .= 'c6OIXAFyFgGd7RaC5wnymSB3AGPlE/uGVJzh45qdTepyacB1HxpEBua19+O524EW7/MH4Gtlyt5N';
        $icon .= 'D3AYcP4g+i6GftfmscALwOvAg8CMQegp4qvAeQNlthTcUoSTm51HCDCdLpYHQoXxNcnc4ppk7nSx';
        $icon .= 'gqNF5QMR+9lMfXiJZWGh7jwJFlZtOJ9DBm6ntm+Yzv69EDOYSV8C5IC1QMRHDwC9BcvBwEGYSRev';
        $icon .= '/+p+jNkf4+CeqAWq+ug3AhNg84EaTJYf56NHgJG9yBjm01kMkLXe32qgVGWygSPxgt6y+jj6qlnh';
        $icon .= 'vKIBp05VZ2cS9s8Bok2d26LJ3K3RFieGyEqFm1XkKhW9Lxiyl7fECfQxWB+kM7irelgvxA+A7d5g';
        $icon .= 'FgJPAsXTmWnAP4FHgNeAqG9gTcA7wL+BTR6vH18HHOBfHj/AnzDB8ijwNMaREWApMApYhnHQ9BJ2';
        $icon .= 'zgHuBDYAnwO3Ae0erQZ4z7PpTeA7vn4LvPFtAV71bCjiFGAjJpMv8rVf6MlZ5o3tmH6367EV7Gzf';
        $icon .= 'kT9H4SuZ+vCSonMENNqU/VtN0jlHVe8QpR4kOkrsG/qTaaDVharOzwfGywuYwR8K3Ap8FxgPXOEN';
        $icon .= 'RoAfA0FMiR0BZDHR7cdc4EZMNjjA94CTMdkyDmgDrvFoS4H/APcCdwN/L2HXyUCr73mtJx/gbc/m';
        $icon .= '8Z7MC732U4EfAt/GlMlUDzurvX4xYLbXNgL4jdd2OvAroDGo9J8p5tTFmZ+ptxtGir0sNdm5LPYo';
        $icon .= 'nUX6uGT+JYWz04nwXIGbWhtCTeOa8m2ZeHgKoqdEW5wFPWWKyIG1y+noT7eHLBD2Bv4U8K7X/oJH';
        $icon .= 'OwI4C7geM/EOJuscn4wG4CPgeLqXijpP7h3e8+GYEudiHPEp8KynoxSqe+jwYyTQiCnhQWC9134m';
        $icon .= 'cA8mMACuAkb7+j0H7AK20r1UnOLZ9HvvOQBMsAAGWuqizU4TXfxWqkM3/GM6B/hpAhpryTUierWl';
        $icon .= '1pWZhuoRWXLrXJXtpQ62VQmW8WvF8cAbGP6e1cLy2pXdN0DvYMpWEeuANYD/7FYxJS/pfRoxGexH';
        $icon .= 'qA+73gRO6oV2J2YtPAG4pIdOv50dmIwvwn88WQwyBd7y2dkETLKAV0cTnNiHgbshttJ5XYfn59v5';
        $icon .= 'yPhSu0Udnr8f5dhoU+e2CUn+2zEsdxcBfuTnScfDowX9cIAqTwUuB5ZjovdMzFoEJtsCwPvAX4Gf';
        $icon .= 'YqK8CrNj9R+mvwdc57VN8tqeB04EXgSeAT7GlOQi2oGjPZnTMEHkxz2YYDjSe57i2QAm497BZM+l';
        $icon .= 'vj5Pes+HYOZvlq9Pb9jg2bHRs/MtoI50vX1cOhG6tZ/OA8ZL8WGHpRJ2Esyv8ZlE6PZMwl7aOjUy';
        $icon .= 'ssiTSoSvSSUifb13PYMpcTswa8rZPtoUzIZjC/AK3RuBEHAfsNP73IUpiQdh1p9fe3x3Ax9iAgLg';
        $icon .= 'Foxj38RkgF/XuZjA2Ak8AXyzhK1XAp9hNiDFSQa4GlN2P8AEzft0v0bM88a3HbMMFHed64FtmGxe';
        $icon .= 'hCmt8zzaDI+2BbNhmyUAmYTdklXn4nKuOpSC98NrsxW07nTdrg51rcOD4dyz+ax9riUaibbk79eF';
        $icon .= 'WJkt9sPRMc40WVj6NWWAOAiTKT0xDFN6cmXICmMypL0EzcIERl/yhmFeE7b3aD8Aswvd484PJpur';
        $icon .= 'MQ4eKALAgRjHEzRt7uKw2HPBuakMQbsh01A9It1VWCJK60lHZ5/f3THOQ8Vvba+FLhShZYiOg9KO';
        $icon .= 'AzNZ5SJH785x+6D5dZbS25djCv3QS6ELun/5+WLNSifsBzXgzCvnzkoqznCscC2qtSJ6CC7LapL5';
        $icon .= 'dG/86+NUhbEfjCadaUO5WlFBD7ROjYxM19tNz00qZuPehYKk6sP3pupD0f65Kygb6Ybgaan60C37';
        $icon .= 'QnYqYf+icodl72LP22Px0EUqRD/dkZ/f80rEYKAg6YT9S5DOWEtunwTGlxUlT/Uz8eAZKtYsUWdO';
        $icon .= 'NPnFSUDZWDeF/auq7EZc1tQknf4OhysoE73emG5riHyry3UXAa876txW7o1p+SQ8E9HTAgWuH7vS';
        $icon .= '2bxXrK1gN/T7vwqpeHCiiDUb9EMlsKZjR/bFUuVUF2K1bQ7WumKdC4xS5IFxLbkn9onVFQADcF4R';
        $icon .= '6Xh4NBbfF9WxKlgoeREiqmQRc/4nyuYu4fFxzc6mfWdyBUX8DxR1MjzQEuhgAAAAAElFTkSuQmCC';

        $data = 'data:image/png;base64,'.$icon;

        return apply_filters('docketcache/filter/resc/iconnav', $data);
    }

    public static function spinner()
    {
        $icon = 'R0lGODlhEAAQAPIAAAAAAP///zw8PHx8fP///wAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh';
        $icon .= '/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADGwi6MjRiSenI';
        $icon .= 'm9hqPOvljAOBZGmeaKqubOu6CQAh+QQJCgAAACwAAAAAEAAQAAADHAi63A5ikCEek2TalftWmPZF';
        $icon .= 'U/WdaKqubOu+bwIAIfkECQoAAAAsAAAAABAAEAAAAxwIutz+UIlBhoiKkorB/p3GYVN1dWiqrmzr';
        $icon .= 'vmkCACH5BAkKAAAALAAAAAAQABAAAAMbCLrc/jDKycQgQ8xL8OzgBg6ThWlUqq5s604JACH5BAkK';
        $icon .= 'AAAALAAAAAAQABAAAAMbCLrc/jDKSautYpAhpibbBI7eOEzZ1l1s6yoJACH5BAkKAAAALAAAAAAQ';
        $icon .= 'ABAAAAMaCLrc/jDKSau9OOspBhnC5BHfRJ7iOXAe2CQAIfkECQoAAAAsAAAAABAAEAAAAxoIutz+';
        $icon .= 'MMpJ6xSDDDEz0dMnduJwZZulrmzbJAAh+QQJCgAAACwAAAAAEAAQAAADGwi63P4wRjHIEBJUYjP/';
        $icon .= '2dZJlIVlaKqubOuyCQAh+QQJCgAAACwAAAAAEAAQAAADHAi63A5ikCEek2TalftWmPZFU/WdaKqu';
        $icon .= 'bOu+bwIAOwAAAAAAAAAAAA==';

        $icon = apply_filters('docketcache/filter/resc/spinner', $icon);

        return 'data:image/gif;base64,'.$icon;
    }

    public static function boxmsg($msg, $type = 'info', $is_dismiss = true, $is_bold = true, $is_hide = true)
    {
        if (!empty($msg) && !empty($type)) {
            $html = '<div id="docket-cache-notice"';
            if ($is_hide) {
                $html .= ' style="display:none;" ';
            }
            $html .= 'class="notice notice-'.$type.($is_dismiss ? ' is-dismissible' : '').'"> ';
            if ($is_bold) {
                $html .= '<p><strong>'.$msg.'</strong></p>';
            } else {
                $html .= '<p>'.$msg.'</p>';
            }
            $html .= '</div>';

            $html = apply_filters('docketcache/filter/resc/boxmsg', $html, $msg, $type, $is_dismiss);

            return $html;
        }
    }

    public static function runtimenotice($action, $is_adr = false)
    {
        $code = WpConfig::runtime_code();
        $is_bedrock = WpConfig::is_bedrock();
        $fname = $is_bedrock ? 'config/application.php' : 'wp-config.php';

        $is_remove = !WpConfig::is_runtimefalse();

        /* translators: %s: file name */
        $text1 = sprintf(__('<strong>Docket Cache</strong> require updating the <code>%s</code> file to handle runtime options.<br>', 'docket-cache'), $fname);
        $text2 = '<br>&bull; '.__('To Install: Copy and insert the code below before <code>require_once ABSPATH . \'wp-settings.php\';</code>', 'docket-cache');
        $text3 = '<br>&bull; '.__('To Install: Copy and insert the code below after <code>Config::apply();</code>.', 'docket-cache');
        $text4 = '<br>'.__('Or click <strong>Install</strong> to update it now.', 'docket-cache');

        if ($is_adr) {
            $textr = '<br>&bull; '.__('To Un-install: Find the code below and remove it manually.', 'docket-cache');

            if ($is_remove) {
                $text2 = '<br>&bull; '.__('To Update: Copy and insert the code below before <code>require_once ABSPATH . \'wp-settings.php\';</code>', 'docket-cache');
                $text3 = '<br>&bull; '.__('To Update: Copy and insert the code below after <code>Config::apply();</code>.', 'docket-cache');
                $text2 .= $textr;
                $text3 .= $textr;
                $text4 = '<br>'.__('Or click <strong>Install</strong> to update it now. Click <strong>Un-Install</strong> to remove the code.', 'docket-cache');
            }
        }

        $message = $text1;
        if ($is_bedrock) {
            $message .= $text3;
        } else {
            $message .= $text2;
        }

        $message .= '<br><br><textarea rows="15" onclick="this.select();document.execCommand(\'copy\');" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" readonly>'.$code.'</textarea>';

        if (WpConfig::is_writable() && !$is_bedrock) {
            $message .= '<br>'.$text4.'<br><a href="'.$action.'" class="button button-primary btx-bti btx-spinner">'.esc_html__('Install', 'docket-cache').'</a>';
            if ($is_remove && $is_adr) {
                $message .= '<a href="'.$is_adr.'" class="button button-secondary btx-btu btx-spinner">'.esc_html__('Un-Install', 'docket-cache').'</a>';
            }
        }

        $message = '<div id="runtimenotice">'.$message.'</div>';
        echo self::boxmsg($message, 'warning', true, false, false);
    }
}
