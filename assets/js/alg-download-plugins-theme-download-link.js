/**
 * Download Plugins and Themes from Dashboard - Theme Download Link JS
 *
 * @version 2.1.0
 * @since   1.1.0
 *
 * @author WPFactory
 */

jQuery(document).ready(function () {
	jQuery("div.theme-actions").each(function () {
		let theme_name = jQuery(this).parents("div.theme").attr("data-slug");
		let url = new URL(algDownloadPluginsDashboard.themesURL);
		let params = new URLSearchParams(url.search);
		params.set("alg_download_theme", theme_name);
		params.set(
			algDownloadPluginsDashboard.nonce.param,
			algDownloadPluginsDashboard.nonce.value,
		);
		url.search = params.toString();
		jQuery(this).append(
			'<a class="button alg_download_theme" href="' +
				url.toString() +
				'">' +
				algDownloadPluginsDashboard.downloadLinkText +
				"</a>",
		);
	});
});
