# ga-popular-posts-geniem
Wordpress plugin that provides a set of methods to fetch and use most popular posts from Google Analytics

## Setup ##

### Google Analytics ###
1. Create a Google Analytics Project
2. Add Custom Dimensions, at least `Post ID` and `Category`
3. Create a new `Content Group` called Single Posts (to filter out front pages etc.)
4. Get the `View ID` from View Settings

### Google Service (API for Analytics) ###
1. Create a new project throught Google Console https://console.developers.google.com/project
2. Activate Google Analytics API for this project
3. Create a new Service Account Key and save it
4. Copy the Service Account ID (which is an email address in style of `oiuwer-3423@coherent-hearing-1098233.iam.gserviceaccount.com`)
5. Add the Service Account ID to Google Analytics users and allow access to `Read & Analyze`

You now should have two things, `View ID` and the Service Account Key JSON file. You'll need these later.

### Google Analytics Tracking Code on your website

You need to do some dynamic customisation in your tracking code to enable single post tracking:

```javascript
<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

    ga('create', 'XXXXXXXXX', 'auto');
{?Single}
    ga('set', 'contentGroup1', 'Single Posts');
    ga('set', 'dimension1', '{Single.Content.ID}');
    ga('set', 'dimension2', '{Single.Content.post_categories[0].slug}');
{/Single}
    ga('send', {
        hitType: 'pageview',
	{?Single}
		title: '{Single.Content.post_title|h}',
	{/Single}
		location: '{WP.permalink}'
    });

</script>
```
Now let the Google Analytics gather some traffic and then you can start fetching stuff.

### Fetching the data from Google Analytics ###
```php
/**
 * Get all single top posts
 */
function getAllSingleTopPosts()
{
    // Create the object
    $gaPopularPosts = new GAPopularPosts();

    // Set Google Service Credentials. This is the contents of the JSON file mentioned earlier
    $gaPopularPosts->setCredentials(GOOGLE_SERVICE_ACCOUNT_CREDENTIALS);

    // Set View ID
    $gaPopularPosts->setViewId(GA_POPULAR_POSTS_VIEW_ID);

    // Set dimensions you want to get
    $gaPopularPosts->setDimensions(array(
        'ga:pageTitle',
        'ga:pagePath',
        'ga:dimension1'
    ));

    // Set filters for the query
    $gaPopularPosts->setFilters(array(
        'ga:contentGroup1' => 'Single Posts'
    ));

    // Set time range
    $gaPopularPosts->setTimeago('30daysago');

    // Get the report
    $report = $gaPopularPosts->getReport();

    // Save the report
    $cache_name = 'ga_all_top_posts';
    update_option($cache_name, $report);
}
```
After running this, you should have data in options table with the name set in the function, in this case 'ga_all_top_posts'. You might want to run this in cronjob to keep the data up to date.

A good way to explore the API and test out filtering is here: http://ga-dev-tools.appspot.com/explorer/
