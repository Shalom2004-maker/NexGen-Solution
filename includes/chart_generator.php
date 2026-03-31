<?php

/**
 * Chart Component for NexGen Solution
 * Reusable chart generator for dashboards
 */

class ChartGenerator
{
    private $conn;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
    }

    /**
     * Generate employee department distribution pie chart
     */
    public function getEmployeeDepartmentChart()
    {
        $query = "
            SELECT
                COALESCE(e.department, 'Unassigned') as department,
                COUNT(*) as employee_count
            FROM employees e
            JOIN users u ON e.user_id = u.id
            WHERE u.status = 'active'
            GROUP BY e.department
            ORDER BY employee_count DESC
        ";

        $result = $this->conn->query($query);
        $dataPoints = array();

        while ($row = $result->fetch_assoc()) {
            $dataPoints[] = array(
                "label" => $row['department'],
                "y" => (int)$row['employee_count']
            );
        }

        return $dataPoints;
    }

    /**
     * Generate leave requests status chart
     */
    public function getLeaveStatusChart()
    {
        $query = "
            SELECT status, COUNT(*) as count
            FROM leave_requests
            GROUP BY status
            ORDER BY count DESC
        ";

        $result = $this->conn->query($query);
        $dataPoints = array();

        while ($row = $result->fetch_assoc()) {
            $dataPoints[] = array(
                "label" => ucfirst(str_replace('_', ' ', $row['status'])),
                "y" => (int)$row['count']
            );
        }

        return $dataPoints;
    }

    /**
     * Generate tasks status chart
     */
    public function getTaskStatusChart()
    {
        $query = "
            SELECT status, COUNT(*) as count
            FROM tasks
            GROUP BY status
            ORDER BY count DESC
        ";

        $result = $this->conn->query($query);
        $dataPoints = array();

        while ($row = $result->fetch_assoc()) {
            $dataPoints[] = array(
                "label" => ucfirst(str_replace('_', ' ', $row['status'])),
                "y" => (int)$row['count']
            );
        }

        return $dataPoints;
    }

    /**
     * Generate monthly leave requests line chart (last 6 months)
     */
    public function getMonthlyLeaveChart()
    {
        $query = "
            SELECT
                DATE_FORMAT(applied_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM leave_requests
            WHERE applied_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(applied_at, '%Y-%m')
            ORDER BY month
        ";

        $result = $this->conn->query($query);
        $dataPoints = array();

        while ($row = $result->fetch_assoc()) {
            $dataPoints[] = array(
                "label" => date('M Y', strtotime($row['month'] . '-01')),
                "y" => (int)$row['count']
            );
        }

        return $dataPoints;
    }

    /**
     * Generate leave types distribution chart
     */
    public function getLeaveTypesChart()
    {
        $query = "
            SELECT leave_type, COUNT(*) as count
            FROM leave_requests
            GROUP BY leave_type
            ORDER BY count DESC
        ";

        $result = $this->conn->query($query);
        $dataPoints = array();

        while ($row = $result->fetch_assoc()) {
            $dataPoints[] = array(
                "label" => ucfirst($row['leave_type']),
                "y" => (int)$row['count']
            );
        }

        return $dataPoints;
    }

    /**
     * Render chart HTML and JavaScript
     */
    public function renderChart($chartId, $dataPoints, $title, $type = 'pie', $options = array())
    {
        $defaultOptions = array(
            'animationEnabled' => true,
            'exportEnabled' => true,
            'theme' => 'light1'
        );

        $chartOptions = array_merge($defaultOptions, $options);
        $chartOptions['title'] = array('text' => $title);
        $chartOptions['data'] = array(array(
            'type' => $type,
            'dataPoints' => $dataPoints
        ));

        // Add index labels for pie charts
        if ($type === 'pie') {
            $chartOptions['data'][0]['indexLabel'] = "{label}: {y}";
        }

        echo '<div id="' . $chartId . '" style="height: 300px; width: 100%; margin-bottom: 20px;"></div>';
        echo '<script>';
        echo 'var chart = new CanvasJS.Chart("' . $chartId . '", ' . json_encode($chartOptions) . ');';
        echo 'chart.render();';
        echo '</script>';
    }
}
