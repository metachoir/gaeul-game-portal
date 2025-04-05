<?php
    // 파일 업로드 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
        $imageDir = 'images/';
        $uploadSuccess = [];
        $uploadError = [];
        
        // 여러 파일 처리
        foreach($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $fileName = $_FILES['images']['name'][$key];
            $fileSize = $_FILES['images']['size'][$key];
            $fileError = $_FILES['images']['error'][$key];
            
            // 기본적인 검증
            if ($fileError === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($extension, $allowedExtensions)) {
                    // 파일명 중복 방지
                    $newFileName = uniqid() . '_' . $fileName;
                    $uploadPath = $imageDir . $newFileName;
                    
                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                        $uploadSuccess[] = $fileName;
                    } else {
                        $uploadError[] = $fileName . ' (업로드 실패)';
                    }
                } else {
                    $uploadError[] = $fileName . ' (지원하지 않는 파일 형식)';
                }
            } else {
                $uploadError[] = $fileName . ' (오류 발생)';
            }
        }

        // 결과를 세션에 저장
        session_start();
        $_SESSION['upload_result'] = [
            'success' => $uploadSuccess,
            'error' => $uploadError
        ];

        // 같은 페이지로 리다이렉트
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    session_start();
    // 세션에서 결과를 가져오고 즉시 삭제
    $uploadSuccess = isset($_SESSION['upload_result']['success']) ? $_SESSION['upload_result']['success'] : [];
    $uploadError = isset($_SESSION['upload_result']['error']) ? $_SESSION['upload_result']['error'] : [];
    unset($_SESSION['upload_result']);
    
    // 윤곽선 변환 결과 메시지 처리
    $outlineMessage = '';
    $outlineStatus = '';
    if (isset($_SESSION['outline_result'])) {
        $outlineStatus = $_SESSION['outline_result']['status'];
        $outlineMessage = $_SESSION['outline_result']['message'];
        unset($_SESSION['outline_result']);
    }

    function getImagesRecursively($dir, $baseDir = '') {
        $images = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // 실제 경로 확인
        $realDir = realpath($dir);
        
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != "..") {
                        $filePath = $dir . '/' . $file;
                        $realFilePath = realpath($filePath);
                        
                        // 파일이 기본 디렉토리 내에 있는지 확인
                        if (strpos($realFilePath, $realDir) === 0) {
                            if (is_dir($filePath)) {
                                // 재귀적으로 하위 디렉토리 탐색
                                $subImages = getImagesRecursively($filePath, $baseDir . $file . '/');
                                $images = array_merge($images, $subImages);
                            } else {
                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                if (in_array($extension, $allowedExtensions)) {
                                    // 상대 경로 저장
                                    $images[] = [
                                        'path' => $baseDir . $file,
                                        'name' => pathinfo($file, PATHINFO_FILENAME)
                                    ];
                                }
                            }
                        }
                    }
                }
                closedir($dh);
            }
        }
        return $images;
    }

    $imageDir = 'images/';
    $outlineDir = 'outlines/';
    
    // outlines 디렉토리 추가
    if (!file_exists($outlineDir)) {
        mkdir($outlineDir, 0777, true);
    }
    
    $images = getImagesRecursively($imageDir);
    $outlines = getImagesRecursively($outlineDir);
    
    // 전체 이미지 (일반 이미지 + 윤곽선 이미지)
    $allImages = array_merge($images, array_map(function($outline) {
        $outline['path'] = 'outlines/' . $outline['path'];
        return $outline;
    }, $outlines));
    
    sort($allImages); // 이미지 경로순 정렬
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="screen-orientation" content="portrait">
    <meta name="orientation" content="portrait">
    <!-- iOS용 -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="색칠놀이">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <title>가을이 색칠놀이</title>
    
    <meta name="theme-color" content="#00a2ff">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon-512.png">

    <style>
        /* 이전 스타일은 동일하게 유지 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #00a2ff;
            font-family: '맑은 고딕', sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .title {
            color: white;
            font-size: 28px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            margin: 0;
        }
        .action-button {
            width: 50px;
            height: 50px;
            background: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }
        .action-button:hover {
            transform: scale(1.1);
        }
        .fullscreen-icon {
            width: 24px;
            height: 24px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300a2ff'%3E%3Cpath d='M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            padding: 10px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .image-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            position: relative;
            padding-bottom: 100%;
            text-decoration: none;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        .image-card img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 15px;
            background: white;
        }
        .image-card.loading::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #f0f0f0;
            z-index: 1;
        }
        .image-card.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            border: 3px solid #ddd;
            border-top-color: #00a2ff;
            border-radius: 50%;
            z-index: 2;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }
        .image-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px;
            font-size: 12px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .empty-message {
            text-align: center;
            color: white;
            font-size: 18px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin: 20px auto;
            max-width: 600px;
        }
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        @media (max-width: 600px) {
            .image-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 5px;
            }
            .title {
                font-size: 24px;
            }
            body {
                padding: 10px;
            }
            .header {
                padding: 10px;
            }
        }

        :fullscreen {
            background: #00a2ff;
            padding: 20px;
            width: 100%;
            height: 100%;
        }
        :-webkit-full-screen {
            background: #00a2ff;
            padding: 20px;
            width: 100%;
            height: 100%;
        }
        :-moz-full-screen {
            background: #00a2ff;
            padding: 20px;
            width: 100%;
            height: 100%;
        }
        
        .upload-button {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer;
            border: none;
            flex-shrink: 0;
            padding: 0;
            transition: transform 0.3s;
        }
        
        .upload-button:hover {
            transform: scale(1.1);
        }
        
        .upload-icon {
            width: 24px;
            height: 24px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300a2ff'%3E%3Cpath d='M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 24px;
        }

        .outline-icon {
            width: 24px;
            height: 24px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300a2ff'%3E%3Cpath d='M3 17h18v2H3zm0-7h18v2H3zm0-7h18v2H3zm11 14l3.5-3.5-1.4-1.4-1.1 1.1V10h-2v10.2l-1.1-1.1-1.4 1.4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 24px;
        }
        
        .upload-input {
            display: none;
        }
        
        .message-container {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        
        .message-success,
        .message-error {
            margin: 5px 0;
            padding: 10px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            color: #333;
            animation: fadeOut 5s forwards;
        }
        
        .message-success {
            border-left: 4px solid #2ecc71;
        }
        
        .message-error {
            border-left: 4px solid #e74c3c;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
        
        .image-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
            z-index: 10;
        }
        
        .image-action-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.8);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .image-action-btn:hover {
            background: white;
            transform: scale(1.1);
        }
        
        .image-card.outline {
            background: #f8f8f8;
            border: 2px dashed #00a2ff;
        }
        
        .outline-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 162, 255, 0.8);
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 10px;
            z-index: 5;
        }
        
        /* 팝업 윤곽선 설정 */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .popup-container {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            max-height: 80%;
            overflow-y: auto;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transform: translateY(20px);
            transition: all 0.3s;
        }
        
        .popup-overlay.active .popup-container {
            transform: translateY(0);
        }
        
        .popup-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: #00a2ff;
            text-align: center;
        }
        
        .popup-content {
            margin-bottom: 20px;
        }
        
        .popup-buttons {
            display: flex;
            justify-content: space-between;
        }
        
        .popup-btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .popup-btn-primary {
            background: #00a2ff;
            color: white;
        }
        
        .popup-btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .popup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .slider-container {
            margin: 15px 0;
        }
        
        .slider-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .slider-value {
            font-weight: bold;
            color: #00a2ff;
        }
        
        .slider {
            width: 100%;
            -webkit-appearance: none;
            height: 10px;
            border-radius: 5px;
            background: #f0f0f0;
            outline: none;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #00a2ff;
            cursor: pointer;
        }
        
        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #00a2ff;
            cursor: pointer;
            border: none;
        }
        
        .preview-container {
            text-align: center;
            margin: 15px 0;
            max-height: 200px;
            overflow: hidden;
            border-radius: 10px;
        }
        
        .preview-container img {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">가을이 색칠놀이</h1>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="action-button upload-button" onclick="document.getElementById('fileInput').click()">
                <div class="upload-icon"></div>
            </button>
            <button class="action-button fullscreen-btn" id="fullscreenButton">
                <div class="fullscreen-icon"></div>
            </button>
        </div>
    </div>

    <form id="uploadForm" method="post" enctype="multipart/form-data" style="display: none;">
        <input type="file" id="fileInput" name="images[]" accept="image/*" multiple>
    </form>

    <div class="image-grid">
        <?php if (empty($allImages)): ?>
            <div class="empty-message">
                색칠할 이미지가 없습니다.
                <br>images 폴더에 이미지를 추가해주세요.
            </div>
        <?php else: ?>
            <?php foreach ($allImages as $image): ?>
                <?php 
                    $isOutline = strpos($image['path'], 'outlines/') === 0;
                    $displayPath = $isOutline ? $image['path'] : 'images/' . $image['path'];
                    $originalPath = $isOutline ? str_replace('outlines/', '', $image['path']) : $image['path'];
                ?>
                <div class="image-card loading <?php echo $isOutline ? 'outline' : ''; ?>">
                    <?php if ($isOutline): ?>
                        <span class="outline-badge">윤곽선</span>
                    <?php endif; ?>
                    
                    <div class="image-actions">
                        <?php if (!$isOutline): ?>
                            <button class="image-action-btn outline-btn" 
                                    onclick="openOutlinePopup('<?php echo htmlspecialchars($image['path']); ?>')" 
                                    title="윤곽선 이미지로 변환">
                                <div style="width: 16px; height: 16px; background-image: url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300a2ff\'%3E%3Cpath d=\'M3 17h18v2H3zm0-7h18v2H3zm0-7h18v2H3z\'/%3E%3C/svg%3E'); background-size: contain;"></div>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <img src="<?php echo htmlspecialchars($displayPath); ?>" 
                         alt="<?php echo htmlspecialchars($image['name']); ?>"
                         loading="lazy"
                         onclick="location.href='drawing.php?image=<?php echo urlencode($image['path']); ?>'"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\' y=\'50\' font-family=\'Arial\' font-size=\'14\' fill=\'%23999\' text-anchor=\'middle\' dy=\'.3em\'%3E이미지 없음%3C/text%3E%3C/svg%3E'">
                    <div class="image-name"><?php echo htmlspecialchars($image['name']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- 윤곽선 변환 팝업 -->
    <div class="popup-overlay" id="outlinePopup">
        <div class="popup-container">
        <div class="popup-title">이미지 윤곽선 변환</div>
            <div class="popup-content">
                <div class="preview-container">
                    <img src="" id="previewImage" alt="미리보기">
                </div>
                <div class="slider-container">
                    <div class="slider-label">
                        <span>윤곽선 민감도:</span>
                        <span class="slider-value" id="thresholdValue">20</span>
                    </div>
                    <input type="range" min="1" max="100" value="20" class="slider" id="thresholdSlider">
                    <p class="slider-help">낮을수록 더 많은 선이 검출됩니다.</p>
                </div>
            </div>
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-secondary" onclick="closeOutlinePopup()">취소</button>
                <button class="popup-btn popup-btn-primary" id="convertButton" onclick="convertToOutline()">변환하기</button>
            </div>
        </div>
    </div>

    <!-- 메시지 표시 영역 -->
    <div class="message-container">
        <?php if (!empty($uploadSuccess)): ?>
            <div class="message-success">
                <?php echo count($uploadSuccess); ?>개의 파일이 업로드되었습니다.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($uploadError)): ?>
            <div class="message-error">
                <?php echo implode('<br>', $uploadError); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($outlineMessage)): ?>
            <div class="message-<?php echo $outlineStatus; ?>">
                <?php echo htmlspecialchars($outlineMessage); ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 화면 방향 고정 시도
            if (screen.orientation && screen.orientation.lock) {
                screen.orientation.lock('portrait')
                    .catch(function(error) {
                        console.log('방향 고정 실패:', error);
                    });
            }
            
            const imageCards = document.querySelectorAll('.image-card');

            // 개별 이미지 로딩 처리
            imageCards.forEach(card => {
                const img = card.querySelector('img');
                
                if (img.complete) {
                    card.classList.remove('loading');
                } else {
                    img.addEventListener('load', () => {
                        card.classList.remove('loading');
                    });
                    img.addEventListener('error', () => {
                        card.classList.remove('loading');
                    });
                }
            });

            // 전체화면 토글
            const fullscreenButton = document.getElementById('fullscreenButton');
            
            function toggleFullscreen() {
                if (!document.fullscreenElement && 
                    !document.mozFullScreenElement && 
                    !document.webkitFullscreenElement && 
                    !document.msFullscreenElement) {
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen();
                    } else if (document.documentElement.mozRequestFullScreen) {
                        document.documentElement.mozRequestFullScreen();
                    } else if (document.documentElement.webkitRequestFullscreen) {
                        document.documentElement.webkitRequestFullscreen();
                    } else if (document.documentElement.msRequestFullscreen) {
                        document.documentElement.msRequestFullscreen();
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                }
            }

            fullscreenButton.addEventListener('click', toggleFullscreen);

            // 파일 선택 시 자동 업로드
            document.getElementById('fileInput').addEventListener('change', function() {
                if (this.files.length > 0) {
                    document.getElementById('uploadForm').submit();
                }
            });
            
            // 슬라이더 값 표시
            const thresholdSlider = document.getElementById('thresholdSlider');
            const thresholdValue = document.getElementById('thresholdValue');
            
            if (thresholdSlider) {
                thresholdSlider.addEventListener('input', function() {
                    thresholdValue.textContent = this.value;
                });
            }
        });

        // 더블탭 줌 방지
        document.addEventListener('dblclick', function(e) {
            e.preventDefault();
        });
        
        // 윤곽선 변환 팝업 관련 함수
        let currentImagePath = '';
        
        function openOutlinePopup(imagePath) {
            currentImagePath = imagePath;
            document.getElementById('previewImage').src = 'images/' + imagePath;
            document.getElementById('outlinePopup').classList.add('active');
            event.stopPropagation();
        }
        
        function closeOutlinePopup() {
            document.getElementById('outlinePopup').classList.remove('active');
        }
        
        function convertToOutline() {
            if (!currentImagePath) return;
            
            const threshold = document.getElementById('thresholdSlider').value;
            window.location.href = 'outline.php?image=' + encodeURIComponent(currentImagePath) + '&threshold=' + threshold;
        }
    </script>
</body>
</html>