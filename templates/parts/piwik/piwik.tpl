{if $kiki.config.piwikHost}
  {if $kiki.config.piwikSiteId}
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://{$kiki.config.piwikHost}/piwik/" : "http://{$kiki.config.piwikHost}/piwik/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", {$kiki.config.piwikSiteId});
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
</script><noscript><p><img src="http://{$kiki.config.piwikHost}/piwik/piwik.php?idsite={$kiki.config.piwikSiteId}" style="border:0" alt=""></p></noscript>
<!-- End Piwik Tracking Code -->
  {/if}
{/if}
