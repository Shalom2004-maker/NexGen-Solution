# Charts Integration Guide - NexGen Solution

## Overview

This guide explains how to integrate interactive charts into your NexGen Solution dashboards using CanvasJS and the custom ChartGenerator class.

## Features Added

- **Employee Distribution Charts**: Visualize employees by department
- **Task Status Charts**: Show task completion status
- **Leave Request Analytics**: Track leave requests by status and trends
- **Interactive Visualizations**: Pie charts, doughnut charts, line charts

## Files Modified/Created

### New Files:

- `includes/chart_generator.php` - Main chart generation class
- `datachart.php` - Updated demo with real employee data

### Modified Files:

- `dashboard/admin_dashboard.php` - Added chart sections

## Chart Types Available

### 1. Employee Department Distribution

```php
$deptData = $chartGen->getEmployeeDepartmentChart();
$chartGen->renderChart('chartId', $deptData, 'Title', 'pie');
```

### 2. Task Status Overview

```php
$taskData = $chartGen->getTaskStatusChart();
$chartGen->renderChart('chartId', $taskData, 'Title', 'doughnut');
```

### 3. Leave Requests Status

```php
$leaveData = $chartGen->getLeaveStatusChart();
$chartGen->renderChart('chartId', $leaveData, 'Title', 'pie');
```

### 4. Monthly Leave Trends

```php
$monthlyData = $chartGen->getMonthlyLeaveChart();
$chartGen->renderChart('chartId', $monthlyData, 'Title', 'line');
```

## Integration Steps

### 1. Include Chart Generator

```php
require_once __DIR__ . "/../includes/chart_generator.php";
$chartGen = new ChartGenerator($conn);
```

### 2. Add CanvasJS Script

```html
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
```

### 3. Create Chart Container

```html
<div id="myChart" style="height: 300px; width: 100%;"></div>
```

### 4. Render Chart

```php
$data = $chartGen->getEmployeeDepartmentChart();
$chartGen->renderChart('myChart', $data, 'Employee Distribution', 'pie');
```

## Chart Options

### Chart Types:

- `pie` - Pie chart (best for distributions)
- `doughnut` - Doughnut chart (modern alternative to pie)
- `line` - Line chart (best for trends over time)
- `column` - Column chart (good for comparisons)
- `bar` - Bar chart (horizontal columns)

### Customization Options:

```php
$options = array(
    'animationEnabled' => true,
    'exportEnabled' => true,
    'theme' => 'light1', // light1, light2, dark1, dark2
    'backgroundColor' => '#f8f9fa'
);
$chartGen->renderChart('chartId', $data, 'Title', 'pie', $options);
```

## Dashboard Integration Examples

### Admin Dashboard

- Employee distribution by department
- Task status overview
- Leave request analytics
- Monthly trends

### HR Dashboard

- Leave requests status (doughnut chart)
- Employee distribution by department (pie chart)
- Monthly leave trends (line chart)
- Leave types distribution (pie chart)

### Project Leader Dashboard

- Task status overview (doughnut chart)
- Project progress completion (column chart)
- Team task distribution (bar chart)
- Leave requests overview (pie chart)

### Employee Dashboard

- Personal task status (doughnut chart)
- Personal leave status (pie chart)

### Project Leader Dashboard

- Task completion rates
- Project progress
- Team performance
- Resource allocation

## Benefits

1. **Visual Data Representation**: Makes complex data easy to understand
2. **Quick Insights**: Users can quickly grasp trends and distributions
3. **Professional Appearance**: Enhances the overall user experience
4. **Data-Driven Decisions**: Helps management make informed decisions
5. **Interactive Elements**: Users can hover, click, and export charts

## Future Enhancements

- Add date range filters
- Implement drill-down functionality
- Create custom chart themes
- Add real-time data updates
- Include more chart types (area, scatter, etc.)

## Testing

1. Access `datachart.php` to see the employee distribution demo
2. Visit the admin dashboard to see integrated charts
3. Test with different data volumes
4. Verify responsive behavior on mobile devices

## Troubleshooting

- **Charts not loading**: Ensure CanvasJS script is included
- **No data showing**: Check database connection and queries
- **Styling issues**: Verify Bootstrap compatibility
- **Performance**: Limit data points for large datasets
