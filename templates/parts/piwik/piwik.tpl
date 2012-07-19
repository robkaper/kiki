{if $config.piwikHost}
  {if $config.piwikSiteId}
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://{$config.piwikHost}/piwik/" : "http://{$config.piwikHost}/piwik/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 1);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script><noscript><p><img src="http://{$config.piwikHost}/piwik/piwik.php?idsite={$config.piwikSiteId}" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Tracking Code -->
  {/if}
{/if}
