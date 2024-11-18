<?php
// Start output buffering
ob_start();

require_once 'db-connection.php';
require_once 'DatabaseManager.php';

// Initialize variables
$db = null;
$connectionError = null;
$results = [];
$tables = [];

// Try to establish database connection
try {
    $db = new DatabaseManager();
    $config = $db->getConfig();
    
    // Handle form submissions and actions
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['execute_query']) && !empty($_POST['sql_query'])) {
            $results = $db->executeQuery($_POST['sql_query']);
            // Redirect after POST to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();
        } elseif (isset($_POST['upload_sql']) && isset($_FILES['sql_file'])) {
            if ($_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
                $sqlContent = file_get_contents($_FILES['sql_file']['tmp_name']);
                if ($sqlContent !== false) {
                    $results = $db->executeQuery($sqlContent);
                } else {
                    $results = [['type' => 'error', 'message' => 'Error reading SQL file']];
                }
            } else {
                $results = [['type' => 'error', 'message' => 'Error uploading file']];
            }
            // Redirect after POST to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action'])) {
        if ($_GET['action'] === 'drop' && isset($_GET['table'])) {
            $results = $db->dropTable($_GET['table']);
            // Redirect after action to prevent resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit();
        }
    }

    // Get tables list if connection is successful
    $tables = $db->getTables();
} catch (Exception $e) {
    $connectionError = $e->getMessage();
}

// Show success message if redirected after successful action
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $results[] = ['type' => 'success', 'message' => 'Operation completed successfully'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script>
        function checkDropQuery(form) {
            const query = form.sql_query.value.toLowerCase().trim();
            if (query.includes('drop table') || query.includes('drop database')) {
                return confirm('Warning: You are about to drop a table/database. Are you sure you want to proceed?');
            }
            return true;
        }

        function toggleTableDetails(tableId) {
            const element = document.getElementById(tableId);
            if (element) {
                element.classList.toggle('hidden');
            }
        }
    </script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Database Manager</h1>

        <?php if ($connectionError): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <div class="font-bold">Remote Database Connection Error</div>
                <p><?php echo htmlspecialchars($connectionError); ?></p>
                <p class="mt-2">Please make sure:</p>
                <ul class="list-disc ml-6">
                    <li>MySQL service is running on the remote server (<?php echo htmlspecialchars($config['host']); ?>)</li>
                    <li>Remote MySQL server is configured to accept external connections</li>
                    <li>Port 3306 is open on the remote server's firewall</li>
                    <li>Database credentials are correct in db-connection.php</li>
                    <li>The database "<?php echo htmlspecialchars($config['database']); ?>" exists</li>
                </ul>
            </div>
        <?php else: ?>
            <!-- Display any results/errors -->
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $result): ?>
                    <?php if ($result['type'] === 'error'): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    <?php elseif ($result['type'] === 'success'): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Tables Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Tables</h2>
                </div>
                
                <?php if (empty($tables)): ?>
                    <p class="text-gray-500">No tables found in the database.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <?php foreach ($tables as $table): ?>
                            <div class="mb-6 border rounded-lg overflow-hidden">
                                <!-- Table Header -->
                                <div class="bg-gray-50 px-6 py-3 flex justify-between items-center cursor-pointer"
                                     onclick="toggleTableDetails('<?php echo htmlspecialchars($table); ?>_details')">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($table); ?></h3>
                                    <div>
                                        <a href="?action=drop&table=<?php echo urlencode($table); ?>" 
                                           onclick="return confirm('Are you sure you want to drop the table \'' + '<?php echo htmlspecialchars($table); ?>' + '\'?')"
                                           class="text-red-600 hover:text-red-900">Drop</a>
                                    </div>
                                </div>

                                <!-- Table Details -->
                                <div id="<?php echo htmlspecialchars($table); ?>_details" class="hidden">
                                    <!-- Column Structure -->
                                    <div class="px-6 py-4 border-t">
                                        <h4 class="font-semibold mb-2">Table Structure</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead>
                                                    <tr>
                                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
                                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Null</th>
                                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Default</th>
                                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Extra</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    <?php foreach ($db->getTableColumns($table) as $column): ?>
                                                        <tr>
                                                            <td class="px-4 py-2"><?php echo htmlspecialchars($column['Field']); ?></td>
                                                            <td class="px-4 py-2"><?php echo htmlspecialchars($column['Type']); ?></td>
                                                            <td class="px-4 py-2"><?php echo htmlspecialchars($column['Null']); ?></td>
                                                            <td class="px-4 py-2"><?php echo htmlspecialchars($column['Key']); ?></td>
                                                            <td class="px-4 py-2"><?php echo $column['Default'] !== null ? htmlspecialchars($column['Default']) : 'NULL'; ?></td>
                                                            <td class="px-4 py-2"><?php echo htmlspecialchars($column['Extra']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Table Data Preview -->
                                    <div class="px-6 py-4 border-t">
                                        <h4 class="font-semibold mb-2">Data Preview (First 10 rows)</h4>
                                        <div class="overflow-x-auto">
                                            <?php 
                                            $records = $db->getTableRecords($table);
                                            if (!empty($records)):
                                                $columns = array_keys($records[0]);
                                            ?>
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead>
                                                        <tr>
                                                            <?php foreach ($columns as $column): ?>
                                                                <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                                                                    <?php echo htmlspecialchars($column); ?>
                                                                </th>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        <?php foreach ($records as $record): ?>
                                                            <tr>
                                                                <?php foreach ($record as $value): ?>
                                                                    <td class="px-4 py-2"><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                                                <?php endforeach; ?>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <p class="text-gray-500">No records found in this table.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SQL Query Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Execute SQL Query</h2>
                <form method="post" class="space-y-4" onsubmit="return checkDropQuery(this);">
                    <div>
                        <textarea name="sql_query" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter your SQL query here..."></textarea>
                    </div>
                    <button type="submit" name="execute_query" 
                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        Execute Query
                    </button>
                </form>
            </div>

            <!-- SQL File Upload Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Upload SQL File</h2>
                <form method="post" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <input type="file" name="sql_file" accept=".sql" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <button type="submit" name="upload_sql" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Upload</button>
                </form>
            </div>

            <!-- Footer -->
            <footer class="mt-6 text-center">
                <p class="text-gray-600">By Yiğit Ali Demir - 
                    <a href="https://github.com/jpntr" class="text-blue-500 hover:underline">github.com/jpntr</a>, 
                    <a href="https://x.com/jpntr26" class="text-blue-500 hover:underline">x.com/jpntr26</a>, 
                    <a href="https://yigitali.com" class="text-blue-500 hover:underline">yigitali.com</a>
                </p>
            </footer>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>
