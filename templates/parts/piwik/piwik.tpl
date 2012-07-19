{if $config.piwikSiteId}
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://robkaper.nl/piwik/" : "http://robkaper.nl/piwik/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 1);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script><noscript><p><img src="http://robkaper.nl/piwik/piwik.php?idsite={$config.piwikSiteId}" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Tracking Code -->
{/if}
