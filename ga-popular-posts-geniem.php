<?php

/**
 * Class GAPopularPosts
 *
 * This class tries to simplify the Google's Analytics SDK to a point
 * it can be used to fetch and use Analytics data to determine the most popular posts
 *
 * Due to Google Analytics, it's SDK and Google Service API's complex nature make sure you read
 * the documentation to fully understand how to make this work.
 */
class GAPopularPosts
{

    /**
     * The Google's Service Account Credentials as a JSON
     * @var string
     */
    private $credentials;

    /**
     * Google Analytics view ID
     * This is different than the tracking ID (http://stackoverflow.com/questions/36898103/what-is-a-viewid-in-google-analytics)
     * @var string
     */
    private $view_id;

    /**
     * The time range
     * @var string
     */
    private $timeago = '30daysago';

    /**
     * Dimensions to be used in the query
     * https://support.google.com/analytics/answer/1033861?hl=en
     * @var array
     */
    private $dimensions = array(
        'ga:pageTitle',
        'ga:pagePath'
    );

    /**
     * Filter to be used in the query
     *
     * The array key is the dimension name and the value is the dimension value to be filtered with
     * ['contentGroup1' => 'Single Posts']
     * @var array
     */
    private $filters = array();

    /**
     * How many results we fetch from the GA
     * @var int
     */
    private $pageSize = 100;

    /**
     * Setter for $credentials
     * @param string $credentials
     */
    public function setCredentials($credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Setter for $view_id
     * @param string $view_id
     */
    public function setViewId($view_id)
    {
        $this->view_id = (string) $view_id;
    }

    /**
     * Setter for $timeago
     * @param string $timeago
     */
    public function setTimeago($timeago)
    {
        $this->timeago = $timeago;
    }

    /**
     * Setter for dimensions to be fetched
     *
     * For example:
     *
     *   setDimensions(array(
     *      'ga:pageTitle',
     *      'ga:pagePath',
     *      'ga:dimension1',
     *      'ga:dimension2'
     *   ));
     *
     * @param array $dimensions
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }

    /**
     * Setter for $filters
     *
     * For example:
     *
     * setFilters(array(
     *     'ga:dimension2' => 'blogs'
     * ));
     *
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * Set the amount of results we fetch per query
     * @param int $pageSize
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    /**
     * Get the actual report from GA
     * @return array
     */
    public function getReport()
    {

        // Create an array from the credendial JSON
        $auth = (array) json_decode($this->credentials);

        // Create and configure a new client object.
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($auth);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new Google_Service_AnalyticsReporting($client);

        // Create the DateRange object.
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($this->timeago);
        $dateRange->setEndDate("today");

        // Create the Metrics object.
        $metrics = new Google_Service_AnalyticsReporting_Metric();
        $metrics->setExpression("ga:pageviews");
        $metrics->setAlias("pageviews");

        // Init Dimensions array
        $dimensions = array();

        // Create a new Google_Service_AnalyticsReporting_Dimension object from every given dimension
        foreach ($this->dimensions as $dimension) {
            $tmp = new Google_Service_AnalyticsReporting_Dimension();
            $tmp->setName($dimension);
            $dimensions[] = $tmp;
            unset($tmp);
        }

        // Init filters array
        $filters = array();

        // Create a new Google_Service_AnalyticsReporting_DimensionFilter object from every given filter
        foreach ($this->filters as $filter => $name) {
            $tmp = new Google_Service_AnalyticsReporting_DimensionFilter();
            $tmp->setDimensionName($filter);
            $tmp->setOperator('EXACT');
            $tmp->setExpressions(array($name));
            $filters[] = $tmp;
            unset($tmp);
        }

        // Create the DimensionFilterClauses
        $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
        $dimensionFilterClause->setFilters($filters);

        // Create sorting
        $sorting = new Google_Service_AnalyticsReporting_OrderBy();
        $sorting->setFieldName('ga:pageviews');
        $sorting->setSortOrder('DESCENDING');

        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($this->view_id);
        $request->setDateRanges($dateRange);
        $request->setMetrics($metrics);
        $request->setDimensions($dimensions);
        $request->setDimensionFilterClauses(array($dimensionFilterClause));
        $request->setOrderBys($sorting);
        $request->setPageSize($this->pageSize);

        // Do the actual request
        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        $reports = $analytics->reports->batchGet($body);

        // Init the top posts array
        $top_posts = array();

        // Set metadata
        $top_posts['fetchedAt'] = date(DATE_COOKIE);
        $top_posts['viewid'] = $this->view_id;
        $top_posts['timeago'] = $this->timeago;
        $top_posts['raw'] = serialize($reports);
        $top_posts['filters'] = $this->filters;

        // Parse the GA data to a simpler form
        foreach ($reports as $report) {
            $data = $report->getData();
            $rows = $data->getRows();

            foreach ($rows as $row) {
                $top_posts['data'][] = $row->getDimensions();
            }
        }
        return $top_posts;
    }
}

