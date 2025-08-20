<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <!-- Tailwind CSS CDN for modern styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include SheetJS (xlsx) library for Excel file generation -->
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        /* Custom styles for 'Sarabun' font. Ensure 'Sarabun' is available or use a fallback. */
        body {
            font-family: 'Sarabun', sans-serif;
        }
        /* Specific styling for table cells to maintain consistent padding and alignment */
        th, td {
            text-align: left;
        }
        /* Style for essay answer column to wrap long text */
        .essay-answer {
            max-width: 300px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body class="p-5 max-w-5xl mx-auto bg-blue-50 text-gray-800 rounded-lg shadow-inner">
    <h2 class="text-center text-4xl font-extrabold text-blue-800 mb-8 mt-4 tracking-wide">แดชบอร์ดข้อมูลแบบทดสอบ</h2>
    
    <div class="search-form flex flex-col sm:flex-row gap-4 mb-10 justify-center items-center p-6 bg-white rounded-xl shadow-md">
        <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
            <label for="userName" class="text-lg font-semibold text-gray-700 whitespace-nowrap">ชื่อผู้เข้าสอบ:</label>
            <input type="text" id="userName" placeholder="ชื่อหรือบางส่วนของชื่อ" class="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent flex-grow text-lg shadow-sm">
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto mt-4 sm:mt-0">
            <label for="date" class="text-lg font-semibold text-gray-700 whitespace-nowrap">วันที่:</label>
            <input type="date" id="date" class="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent text-lg shadow-sm">
        </div>
        <button onclick="fetchData()" class="px-7 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-200 ease-in-out transform hover:scale-105 mt-6 sm:mt-0 w-full sm:w-auto">
            ค้นหา
        </button>
    </div>

    <div id="loading" class="text-center text-gray-600 text-xl font-medium my-12 p-4 bg-yellow-50 border border-yellow-200 rounded-lg shadow-sm">กรุณากรอกข้อมูลเพื่อเริ่มการค้นหา...</div>
    
    <table id="dataTable" class="min-w-full bg-white shadow-xl rounded-lg overflow-hidden border border-gray-200" style="display:none;">
        <thead class="bg-blue-700 text-white">
            <tr>
                <th class="py-4 px-5 uppercase font-bold text-sm tracking-wider">ชื่อผู้เข้าสอบ</th>
                <th class="py-4 px-5 uppercase font-bold text-sm tracking-wider">คะแนนปรนัย</th>
                <th class="py-4 px-5 uppercase font-bold text-sm tracking-wider">คำตอบอัตนัย</th>
                <th class="py-4 px-5 uppercase font-bold text-sm tracking-wider">ไฟล์เสียง</th>
                <th class="py-4 px-5 uppercase font-bold text-sm tracking-wider">ดาวน์โหลด</th>
            </tr>
        </thead>
        <tbody class="text-gray-700 divide-y divide-gray-200">
            <!-- Table rows will be inserted here by JavaScript -->
        </tbody>
    </table>

    <div class="mt-12 text-center">
        <button id="downloadButton" onclick="downloadXLSX()" class="px-8 py-4 bg-green-600 text-white font-extrabold text-lg rounded-xl shadow-xl hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75 transition duration-200 ease-in-out transform hover:scale-105" style="display:none;">
            📥 ดาวน์โหลดข้อมูล (.xlsx)
        </button>
    </div>

    <script>
        let currentTableData = []; // Global variable to store fetched data for XLSX export

        // Function to fetch data from the server based on user input
        async function fetchData() {
            const userName = document.getElementById('userName').value;
            const date = document.getElementById('date').value;

            // Display loading message and hide table/download button
            document.getElementById('loading').textContent = 'กำลังโหลดข้อมูล...';
            document.getElementById('loading').style.display = 'block';
            document.getElementById('dataTable').style.display = 'none';
            document.getElementById('downloadButton').style.display = 'none'; 

            // Construct the URL for the PHP backend
            // Ensure this path is correct relative to your dashboard.php or dashboard.html file
            let url = 'get_data.php'; 
            const params = new URLSearchParams();
            if (userName) {
                params.append('user', userName);
            }
            if (date) {
                params.append('date', date);
            }
            
            if (params.toString()) {
                url += '?' + params.toString();
            }

            try {
                // Make the fetch request to the PHP backend
                const response = await fetch(url);
                if (!response.ok) {
                    // Handle HTTP errors (e.g., 404, 500)
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json(); // Parse the JSON response
                
                // If the backend sends an error message in JSON
                if (data.error) {
                    document.getElementById('loading').textContent = data.error;
                    return;
                }
                
                currentTableData = data; // Store the fetched data for XLSX export
                renderTable(data); // Render the data in the HTML table
            } catch (error) {
                // Catch any network or parsing errors
                console.error('Error fetching data:', error);
                document.getElementById('loading').textContent = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
            }
        }

        // Function to render the fetched data into the HTML table
        function renderTable(data) {
            const tableBody = document.querySelector('#dataTable tbody');
            tableBody.innerHTML = ''; // Clear existing table rows

            if (data.length === 0) {
                // If no data is returned, display a message and hide the table/button
                document.getElementById('loading').textContent = 'ไม่พบข้อมูลที่ตรงกับการค้นหา';
                document.getElementById('loading').style.display = 'block';
                document.getElementById('dataTable').style.display = 'none';
                document.getElementById('downloadButton').style.display = 'none'; 
                return;
            }

            // Iterate through each user's data to populate the table
            data.forEach(user => {
                // Determine the number of rows needed for this user (at least 1 for main info)
                const rowCount = Math.max(user.recordings.length, 1);
                
                // Create the main table row for user's primary details
                const mainRow = document.createElement('tr');
                mainRow.classList.add('hover:bg-gray-100', 'transition', 'duration-150', 'ease-in-out'); 
                mainRow.innerHTML = `
                    <td rowspan="${rowCount}" class="py-3 px-4 border-b border-gray-200">${user.user}</td>
                    <td rowspan="${rowCount}" class="py-3 px-4 border-b border-gray-200">${user.score !== null ? user.score + ' / 100' : 'ไม่มีข้อมูล'}</td>
                    <td rowspan="${rowCount}" class="py-3 px-4 border-b border-gray-200 essay-answer">${user.essay_answer !== null ? user.essay_answer : 'ไม่มีคำตอบ'}</td>
                `;

                // Add the first recording's details or a "No file" message
                if (user.recordings && user.recordings.length > 0) {
                    mainRow.innerHTML += `
                        <td class="py-3 px-4 border-b border-gray-200">${user.recordings[0].filename}</td>
                        <td class="py-3 px-4 border-b border-gray-200"><a href="${user.recordings[0].path}" download class="text-blue-600 hover:underline">ดาวน์โหลด</a></td>
                    `;
                } else {
                    mainRow.innerHTML += `
                        <td class="py-3 px-4 border-b border-gray-200 text-gray-500 italic">ไม่มีไฟล์</td>
                        <td class="py-3 px-4 border-b border-gray-200"></td>
                    `;
                }
                tableBody.appendChild(mainRow);

                // Add additional rows for subsequent recordings if a user has more than one
                for (let i = 1; i < user.recordings.length; i++) {
                    const fileRow = document.createElement('tr');
                    fileRow.classList.add('hover:bg-gray-100', 'transition', 'duration-150', 'ease-in-out');
                    fileRow.innerHTML = `
                        <td class="py-3 px-4 border-b border-gray-200">${user.recordings[i].filename}</td>
                        <td class="py-3 px-4 border-b border-gray-200"><a href="${user.recordings[i].path}" download class="text-blue-600 hover:underline">ดาวน์โหลด</a></td>
                    `;
                    tableBody.appendChild(fileRow);
                }
            });

            // Hide loading message and show the populated table and download button
            document.getElementById('loading').style.display = 'none';
            document.getElementById('dataTable').style.display = 'table';
            document.getElementById('downloadButton').style.display = 'block'; 
        }

        // Function to download the current table data as an XLSX file
        function downloadXLSX() {
            if (currentTableData.length === 0) {
                // If there's no data, do nothing (no alert, as per guidelines)
                return;
            }

            const exportData = [];
            // Add header row for the XLSX file based on user's request, including 'วันที่' and 'เวลา'
            const headers = ['ชื่อ-นามสกุล', 'คะแนนสอบ', 'คำตอบอัตนัย', 'วันที่', 'เวลา'];
            exportData.push(headers);

            // Get the date from the search input
            let displayDate = document.getElementById('date').value;
            // If search date is empty, use current date
            if (!displayDate) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
                const day = String(today.getDate()).padStart(2, '0');
                displayDate = `${year}-${month}-${day}`;
            }

            // Get the current time of export
            const currentTime = new Date().toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit', second: '2-digit'});

            // Prepare data for XLSX export, including only the requested fields + Date and Time
            currentTableData.forEach(user => {
                exportData.push([
                    user.user,
                    user.score !== null ? user.score + ' / 100' : 'ไม่มีข้อมูล',
                    user.essay_answer !== null ? user.essay_answer : 'ไม่มีคำตอบ',
                    displayDate, // Use the determined date
                    currentTime // Use current export time
                ]);
            });

            // Create a new worksheet from the array of arrays
            const ws = XLSX.utils.aoa_to_sheet(exportData);

            // Apply bold style to header cells (first row)
            const range = XLSX.utils.decode_range(ws['!ref']); // Get the range of cells in the worksheet
            for (let C = range.s.c; C <= range.e.c; ++C) { // Iterate through columns in the first row
                const cellref = XLSX.utils.encode_cell({c: C, r: range.s.r}); // Get cell reference (e.g., A1, B1)
                if (ws[cellref]) {
                    if (!ws[cellref].s) ws[cellref].s = {}; // Ensure style object exists
                    if (!ws[cellref].s.font) ws[cellref].s.font = {}; // Ensure font style object exists
                    ws[cellref].s.font.bold = true; // Set bold to true
                }
            }

            // Create a new workbook
            const wb = XLSX.utils.book_new();
            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "สรุปข้อมูลแบบทดสอบ");
            // Generate and trigger the download of the XLSX file
            XLSX.writeFile(wb, "สรุปข้อมูลแบบทดสอบ.xlsx");
        }
    </script>
</body>
</html>
```


