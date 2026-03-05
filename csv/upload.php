<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') die("Access denied");
?>
<?php include '../includes/header.php'; ?>

<div class="upload-container">
    <div class="upload-card">
        <div class="upload-header">
            <i class="fas fa-cloud-upload-alt"></i>
            <h2>Upload CSV File</h2>
            <p>Import chemical data in bulk. Make sure your file follows the template format.</p>
        </div>

        <form action="process.php" method="post" enctype="multipart/form-data" class="upload-form">
            <div class="file-drop-area" id="fileDropArea">
                <i class="fas fa-file-csv"></i>
                <p class="mb-2">Drag & drop your CSV file here</p>
                <span class="or-divider">or</span>
                <label for="csv_file" class="btn-file-label">
                    <i class="fas fa-folder-open"></i> Browse Files
                </label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" class="file-input" required>
                <div class="file-name mt-2" id="fileName"></div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-upload">
                    <i class="fas fa-upload"></i> Upload Now
                </button>
                <a href="download_template.php" class="btn-template">
                    <i class="fas fa-download"></i> Download Template
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    .upload-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 70vh;
        padding: 2rem 1rem;
    }

    .upload-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-radius: 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
        max-width: 600px;
        width: 100%;
        border: 1px solid rgba(255, 255, 255, 0.5);
        transition: transform 0.3s;
    }

    .upload-card:hover {
        transform: translateY(-5px);
    }

    .upload-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .upload-header i {
        font-size: 3.5rem;
        background: linear-gradient(45deg, #4776E6, #8E54E9);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 1rem;
    }

    .upload-header h2 {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .upload-header p {
        color: #666;
        font-weight: 300;
    }

    .file-drop-area {
        border: 2px dashed #c0c0c0;
        border-radius: 30px;
        padding: 2.5rem 1.5rem;
        text-align: center;
        background: rgba(255, 255, 255, 0.5);
        transition: all 0.3s;
        cursor: pointer;
    }

    .file-drop-area:hover,
    .file-drop-area.dragover {
        border-color: #4776E6;
        background: rgba(71, 118, 230, 0.05);
    }

    .file-drop-area i {
        font-size: 3rem;
        color: #4776E6;
        margin-bottom: 1rem;
    }

    .or-divider {
        display: block;
        margin: 0.5rem 0;
        color: #888;
        font-size: 0.9rem;
    }

    .btn-file-label {
        display: inline-block;
        background: linear-gradient(45deg, #4776E6, #8E54E9);
        color: white;
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(71, 118, 230, 0.3);
    }

    .btn-file-label:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(71, 118, 230, 0.4);
    }

    .file-input {
        display: none;
    }

    .file-name {
        color: #555;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-upload {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        margin-right: 10px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-upload:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(40, 167, 69, 0.4);
    }

    .btn-template {
        background: #f1f3f5;
        color: #555;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-template:hover {
        background: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        color: #333;
    }

    @media (max-width: 576px) {
        .upload-card {
            padding: 1.5rem;
        }
        .btn-upload, .btn-template {
            width: 100%;
            margin: 5px 0;
            justify-content: center;
        }
    }
</style>

<script>
    // Display selected file name
    document.getElementById('csv_file').addEventListener('change', function(e) {
        let fileName = e.target.files[0] ? e.target.files[0].name : '';
        document.getElementById('fileName').textContent = fileName;
    });

    // Drag & drop highlight
    let dropArea = document.getElementById('fileDropArea');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
    });

    dropArea.addEventListener('drop', (e) => {
        let dt = e.dataTransfer;
        let files = dt.files;
        document.getElementById('csv_file').files = files;
        document.getElementById('fileName').textContent = files[0] ? files[0].name : '';
    });
</script>

<?php include '../includes/footer.php'; ?>