<?php
$imageDir = 'images/';
$image = isset($_GET['image']) ? $_GET['image'] : '';
$imagePath = $imageDir . $image;

// 보안 검사
$realImagePath = realpath($imagePath);
$realImageDir = realpath($imageDir);

// 이미지 파일 존재 여부와 보안 검사
$isValidImage = false;
$errorMessage = '';

if (empty($image)) {
    $errorMessage = '이미지가 선택되지 않았습니다.';
} elseif (!file_exists($imagePath)) {
    $errorMessage = '이미지를 찾을 수 없습니다.';
} elseif (strpos($realImagePath, $realImageDir) !== 0) {
    $errorMessage = '잘못된 접근입니다.';
} else {
    $extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $allowedExtensions)) {
        $errorMessage = '지원하지 않는 이미지 형식입니다.';
    } else {
        $isValidImage = true;
    }
}
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
            position: fixed;
        }
        body {
            background: #00a2ff;
            font-family: '맑은 고딕', sans-serif;
        }
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 20px;
            text-align: center;
            color: white;
        }
        .error-message {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .back-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            min-height: 70px;
        }
        .action-button {
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
        }
        .canvas-wrapper {
            flex: 1;
            background: white;
            border-radius: 10px;
            margin: 10px;
            border: 5px solid white;
            position: relative;
            overflow: hidden;
            min-height: 0;
        }
        #canvas {
            width: 100%;
            height: 100%;
            touch-action: none;
            position: absolute;
            top: 0;
            left: 0;
        }
        .color-palette {
            height: 100px;
            min-height: 100px;
            background: rgba(0, 162, 255, 0.8);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: flex-start; /* 변경: center에서 flex-start로 */
            gap: 8px;
            padding: 10px 20px; /* 변경: 좌우 패딩 증가 */
            margin: 0 10px 10px 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .color-button {
            width: 45px;
            height: 70px;
            border-radius: 5px 5px 0 0;
            border: 3px solid white;
            cursor: pointer;
            transition: transform 0.2s, border-color 0.2s;
            flex-shrink: 0;
            margin: 0 2px; /* 추가: 좌우 마진 추가 */
        }

        .color-button:hover, 
        .color-button.active {
            transform: scale(1.1);
            border-color: #FFF;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .color-palette::before,
        .color-palette::after {
            content: '';
            min-width: 5px; /* 최소 여백 확보 */
        }

        /* 스크롤바 스타일링 */
        .color-palette::-webkit-scrollbar {
            height: 8px;
        }

        .color-palette::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .color-palette::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 4px;
        }

        .color-palette::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.7);
        }
        .back-button {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2300a2ff' viewBox='0 0 24 24'%3E%3Cpath d='M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 30px;
        }
        .trash-button {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2300a2ff' viewBox='0 0 24 24'%3E%3Cpath d='M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 30px;
        }
        .save-button {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2300a2ff' viewBox='0 0 24 24'%3E%3Cpath d='M4 4v14c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V7l-3-3H6c-1.1 0-2 .9-2 2zm12 14H8v-4h8v4zm-1-5H9V9h6v4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 30px;
        }
        .file-name {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 30px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80%;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            z-index: 100;
        }
        .drawing-tools {
            display: flex;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 10px;
            margin: 10px;
        }
        
        .tool-button {
            width: 50px;
            height: 50px;
            background: white;
            border: 2px solid #00a2ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tool-button.active {
            background: #00a2ff;
        }
        
        .tool-button.active svg path {
            fill: white;
        }
        
        .brush-icon {
            width: 24px;
            height: 24px;
        }
        
        .eraser-icon {
            width: 24px;
            height: 24px;
        }
    </style>
</head>
<body>
<?php if (!$isValidImage): ?>
    <div class="error-container">
        <div class="error-message">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <a href="index.php" class="back-link">처음으로 돌아가기</a>
    </div>
<?php else: ?>
    <div class="app-container">
        <div class="top-bar">
            <button class="action-button back-button" onclick="location.href='index.php'"></button>
            <div class="file-name"><?php echo htmlspecialchars(pathinfo($image, PATHINFO_FILENAME)); ?></div>
            <div style="display: flex; gap: 10px;">
                <button class="action-button trash-button" onclick="clearCanvas()"></button>
                <button class="action-button save-button" onclick="downloadImage()"></button>
            </div>
        </div>
        
        <div class="canvas-wrapper">
            <canvas id="canvas"></canvas>
        </div>
        <div class="color-palette">
            <!-- 빨간색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #FF0000, #FF3333)" onclick="setColor('#FF0000')" title="빨강"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #FF69B4, #FFB6C1)" onclick="setColor('#FF69B4')" title="분홍"></div>
            
            <!-- 주황색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #FFA500, #FFB84D)" onclick="setColor('#FFA500')" title="주황"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #FF4500, #FF6347)" onclick="setColor('#FF4500')" title="진한 주황"></div>
            
            <!-- 노란색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #FFD700, #FFE44D)" onclick="setColor('#FFD700')" title="노랑"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #FFFF00, #FFFF66)" onclick="setColor('#FFFF00')" title="밝은 노랑"></div>
            
            <!-- 초록색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #32CD32, #90EE90)" onclick="setColor('#32CD32')" title="연두"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #008000, #228B22)" onclick="setColor('#008000')" title="초록"></div>
            
            <!-- 파란색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #87CEEB, #B0E0E6)" onclick="setColor('#87CEEB')" title="하늘색"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #0000FF, #4169E1)" onclick="setColor('#0000FF')" title="파랑"></div>
            
            <!-- 보라색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #800080, #BA55D3)" onclick="setColor('#800080')" title="보라"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #9932CC, #DDA0DD)" onclick="setColor('#9932CC')" title="연한 보라"></div>
            
            <!-- 갈색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #8B4513, #A0522D)" onclick="setColor('#8B4513')" title="갈색"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #DEB887, #F4A460)" onclick="setColor('#DEB887')" title="연한 갈색"></div>
            
            <!-- 무채색 계열 -->
            <div class="color-button" style="background: linear-gradient(45deg, #000000, #333333)" onclick="setColor('#000000')" title="검정"></div>
            <div class="color-button" style="background: linear-gradient(45deg, #808080, #A9A9A9)" onclick="setColor('#808080')" title="회색"></div>
        </div>

        <div class="drawing-tools">
            <button class="tool-button active" id="brushTool" onclick="setTool('brush')" title="붓">
                <svg class="brush-icon" viewBox="0 0 24 24" fill="#00a2ff">
                    <path d="M7 14c-1.66 0-3 1.34-3 3 0 1.31-1.16 2-2 2 .92 1.22 2.49 2 4 2 2.21 0 4-1.79 4-4 0-1.66-1.34-3-3-3zm13.71-9.37l-1.34-1.34c-.39-.39-1.02-.39-1.41 0L9 12.25 11.75 15l8.96-8.96c.39-.39.39-1.02 0-1.41z"/>
                </svg>
            </button>
            <button class="tool-button" id="eraserTool" onclick="setTool('eraser')" title="지우개">
                <svg class="eraser-icon" viewBox="0 0 24 24" fill="#00a2ff">
                    <path d="M15.14 3c-.51 0-1.02.2-1.41.59L2.59 14.73c-.78.78-.78 2.05 0 2.83l3.85 3.85c.78.78 2.05.78 2.83 0L20.41 10.27c.78-.78.78-2.05 0-2.83l-3.85-3.85c-.39-.39-.9-.59-1.42-.59zM5.41 20L4 18.59l7.72-7.72 1.47 1.35L5.41 20z"/>
                </svg>
            </button>
        </div>
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
        });
        
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        let currentColor = '#ff3366';
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        let currentTool = 'brush';
        let autoSaveInterval;
        const AUTOSAVE_DELAY = 3000; // 3초마다 자동 저장
        const currentImageKey = 'drawing_' + <?php echo json_encode($image); ?>; // 각 이미지별 고유 키

        // 캔버스 내용 저장 함수
        function saveCanvasState() {
            try {
                localStorage.setItem(currentImageKey, canvas.toDataURL());
            } catch (e) {
                console.log('저장 실패:', e);
            }
        }

        // 저장된 캔버스 내용 복원 함수
        function restoreCanvasState() {
            try {
                const savedData = localStorage.getItem(currentImageKey);
                if (savedData) {
                    const savedImage = new Image();
                    savedImage.onload = function() {
                        ctx.drawImage(savedImage, 0, 0);
                    };
                    savedImage.src = savedData;
                }
            } catch (e) {
                console.log('복원 실패:', e);
            }
        }

        function setTool(tool) {
            currentTool = tool;
            // 도구 버튼 활성화 상태 변경
            document.getElementById('brushTool').classList.toggle('active', tool === 'brush');
            document.getElementById('eraserTool').classList.toggle('active', tool === 'eraser');
        }

        function resizeCanvas() {
            const wrapper = canvas.parentElement;
            canvas.width = wrapper.clientWidth;
            canvas.height = wrapper.clientHeight;
            if (img.complete) {
                drawImage();
            }
        }

        const img = new Image();
        img.src = <?php echo json_encode($imagePath); ?>;
        img.onload = function() {
            resizeCanvas();
            // 기본 이미지 그린 후 저장된 상태 복원
            setTimeout(() => {
                restoreCanvasState();
            }, 100);
            
            // 자동 저장 시작
            autoSaveInterval = setInterval(saveCanvasState, AUTOSAVE_DELAY);
        };

        function drawImage() {
            const scale = Math.min(
                canvas.width / img.width,
                canvas.height / img.height
            );
            const x = (canvas.width - img.width * scale) / 2;
            const y = (canvas.height - img.height * scale) / 2;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
        }

        function setColor(color) {
            currentColor = color;
            currentTool = 'brush'; // 색상 선택시 자동으로 브러시 모드로 전환
            document.querySelectorAll('.color-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById('brushTool').classList.add('active');
            document.getElementById('eraserTool').classList.remove('active');
            event.currentTarget.classList.add('active');
        }

        function getCoordinates(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDrawing(e) {
            isDrawing = true;
            const coords = getCoordinates(e);
            lastX = coords.x;
            lastY = coords.y;
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            
            const coords = getCoordinates(e);
            ctx.lineWidth = currentTool === 'eraser' ? 20 : 5; // 지우개는 더 두껍게
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            if (currentTool === 'eraser') {
                // 지우개 모드: destination-out 사용
                ctx.globalCompositeOperation = 'destination-out';
                ctx.strokeStyle = 'rgba(0,0,0,1)';
            } else {
                // 브러시 모드: 일반 그리기
                ctx.globalCompositeOperation = 'source-over';
                ctx.strokeStyle = currentColor;
            }

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();

            lastX = coords.x;
            lastY = coords.y;
        }

        function stopDrawing() {
            isDrawing = false;
            // 그리기 종료 시 항상 source-over로 복귀
            ctx.globalCompositeOperation = 'source-over';
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing, { passive: true });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
        canvas.addEventListener('touchcancel', stopDrawing);

        // 기존 함수들 유지
        function clearCanvas() {
            drawImage();
            localStorage.removeItem(currentImageKey);
        }

        function downloadImage() {
            const link = document.createElement('a');
            const fileName = <?php echo json_encode(pathinfo($image, PATHINFO_FILENAME)); ?>;
            link.download = fileName + '_색칠하기.png';
            link.href = canvas.toDataURL();
            link.click();
        }
        
        // 페이지 언로드 시 마지막 상태 저장
        window.addEventListener('beforeunload', function() {
            saveCanvasState();
            clearInterval(autoSaveInterval);
        });

        // resize 이벤트 수정 (크기 조정 시 내용 유지)
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.drawImage(canvas, 0, 0);
                
                resizeCanvas();
                
                ctx.drawImage(tempCanvas, 0, 0, canvas.width, canvas.height);
            }, 100);
        });

        document.addEventListener('dblclick', function(e) {
            e.preventDefault();
        });

        // 초기 활성 색상 설정
        document.querySelector('.color-button').classList.add('active');
    </script>
<?php endif; ?>
</body>
</html>