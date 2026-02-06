<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
include 'header.php';
?>



<div class="monitor-dashboard pt-3 pb-4">
    <div class="container-fluid px-4">
        <!-- Header Section (Compact) -->
        <div class="row align-items-center mb-3">
            <div class="col-md-8">
                <h1 class="fw-bold text-dark mb-0" style="font-size: 1.8rem;">نظام المراقبة الذكي <span
                        class="text-primary">HealthPro</span></h1>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="clock-card apple-card d-inline-block px-3 py-1 text-center bg-white shadow-sm border-0">
                    <span id="live-clock" class="fw-bold text-primary" style="font-size: 1.4rem;">00:00:00</span>
                    <span class="ms-2 small text-muted fw-bold"><?php echo date('Y/m/d'); ?></span>
                </div>
            </div>
        </div>

        <!-- Audio Visual Status -->


        <!-- 3-Column Monitor Screen -->
        <div class="monitor-grid">
            <!-- 1. Registration / Triage (Merged Queue) -->
            <div class="monitor-col">
                <div class="stage-header d-flex align-items-center mb-2">
                    <div class="stage-num">1</div>
                    <div class="ms-2">
                        <div class="fw-bold mb-0" style="font-size: 1rem;">الاستقبال والفحص الأولي</div>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark ms-auto border" id="count-reception">0</span>
                </div>
                <div id="list-reception" class="list-container"></div>
            </div>

            <!-- 2. Doctor -->
            <div class="monitor-col">
                <div class="stage-header d-flex align-items-center mb-2">
                    <div class="stage-num bg-info text-white">2</div>
                    <div class="ms-2">
                        <div class="fw-bold mb-0" style="font-size: 1rem;">انتظار الطبيب</div>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark ms-auto border" id="count-doctor">0</span>
                </div>
                <div id="list-doctor" class="list-container"></div>
            </div>

            <!-- 3. Labs/Rads/Pharmacy (Medical Depts) -->
            <div class="monitor-col">
                <div class="stage-header d-flex align-items-center mb-2">
                    <div class="stage-num bg-dark text-white">3</div>
                    <div class="ms-2">
                        <div class="fw-bold mb-0" style="font-size: 1rem;">المختبر والأشعة والصيدلية</div>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark ms-auto border" id="count-medical">0</span>
                </div>
                <div id="list-medical" class="list-container"></div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --stage-bg: rgba(245, 245, 247, 0.7);
        --accent-color: #0071e3;
    }

    .monitor-dashboard {
        background: #fbfbfd;
        min-height: 100vh;
        overflow: hidden;
    }

    .monitor-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        align-items: start;
    }

    .monitor-col {
        background: var(--stage-bg);
        border-radius: 15px;
        padding: 10px;
        min-height: 90vh;
        /* Fill screen height */
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
    }

    .stage-num {
        width: 24px;
        height: 24px;
        background: var(--accent-color);
        color: white;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .list-container {
        display: flex;
        flex-direction: column;
        gap: 4px;
        /* Very tight gap to fit 30 names */
        overflow: hidden;
        /* No scroll */
    }

    .patient-card {
        background: white;
        border-radius: 6px;
        padding: 4px 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.01);
        border-right: 3px solid var(--accent-color);
        transition: all 0.3s;
    }

    .p-name {
        font-size: 0.85rem;
        /* Small font to fit 30 items */
        font-weight: 700;
        color: #1d1d1f;
        margin-bottom: 0px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .p-meta {
        font-size: 0.65rem;
        color: #86868b;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .entrance-tag {
        font-weight: 600;
    }

    .timer-tag {
        color: var(--accent-color);
        font-weight: 700;
    }

    #live-clock {
        font-variant-numeric: tabular-nums;
        letter-spacing: 1px;
    }

    @media print {
        .no-print {
            display: none;
        }
    }

    @keyframes urgentGlow {
        0% {
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.2);
        }

        50% {
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.5);
        }

        100% {
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.2);
        }
    }

    .shadow-urgent {
        animation: urgentGlow 1.5s infinite;
        border-right: 5px solid #dc3545 !important;
    }
</style>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('live-clock').innerText = now.toLocaleTimeString('en-GB');
    }
    setInterval(updateClock, 1000);
    updateClock();

</script>


<script>
    async function loadMonitorData() {
        try {
            const resp = await fetch('api_waiting.php');
            const data = await resp.json();

            // 1. Column 1: Reception + Triage
            renderList('list-reception', data.reception, 'count-reception', p => `
<div class="patient-card" style="border-right-color: #666;">
    <div class="p-name">${p.name}</div>
    <div class="p-meta">
        <span class="entrance-tag">${p.entrance} - <span class="text-primary">${p.sub_status}</span></span>
        <span class="timer-tag">${p.wait}د</span>
    </div>
</div>
`);

            // 2. Column 2: Doctor
            renderList('list-doctor', data.doctor, 'count-doctor', p => {
                let cardClass = p.is_urgent ? 'border-danger shadow-urgent' : (p.is_ready ? 'border-success' : 'border-primary');
                let bgStyle = p.is_urgent ? 'background: #fff5f5;' : (p.is_ready ? 'background: #f4fff7;' : 'background: white;');



                return `
                <div class="patient-card ${cardClass}" style="border-right-width: 5px; ${bgStyle}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="p-name ${p.is_urgent ? 'text-danger' : ''}">
                            ${p.is_urgent ? '<i class="fas fa-exclamation-circle animate__animated animate__flash animate__infinite me-1"></i>' : ''}
                            ${p.patient}
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            ${p.is_ready ? '<span class="badge bg-success rounded-pill" style="font-size:0.6rem;">مكتمل ✓</span>' : ''}
                        </div>
                    </div>
                    <div class="p-meta mt-1">
                        <span class="entrance-tag">${p.entrance} <span class="ms-1" style="font-size:0.6rem;">د.${p.doctor}</span></span>
                        <span class="timer-tag">${p.wait}د</span>
                    </div>
                </div>
                `;
            });

            // 3. Column 3: Medical (Pending Exams)
            renderList('list-medical', data.medical, 'count-medical', p => `
                <div class="patient-card" style="border-right-color: #da2c38;">
                    <div class="p-name">${p.patient}</div>
                    <div class="p-meta">
                        <span class="entrance-tag">${p.entrance}</span>
                        <div class="d-flex gap-1">
                            ${p.has_lab ? '<span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:0.55rem;">مختبر</span>' : ''}
                            ${p.has_rad ? '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:0.55rem;">أشعة</span>' : ''}
                            ${p.has_pharma ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.55rem;">صيدلية</span>' : ''}
                        </div>
                    </div>
                </div>
            `);

        } catch (e) {
            console.error("Update error: ", e);
            // DEBUG: Show error to user if it's a parsing error
            if (e.name === 'SyntaxError') {
                // Try to fetch text to see what happened
                fetch('api_waiting.php').then(r => r.text()).then(txt => {
                    console.log("Raw Response:", txt);
                    // Only alert if it's not a temporary network blip
                    if (!txt.trim().startsWith('{')) {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في البيانات',
                            text: 'استجابة الخادم غير صالحة. الرجاء ابلاغ الدعم.\n' + txt.substring(0, 100)
                        });
                    }
                });
            }
        }
    }

    function renderList(targetId, array, countId, templateFn) {
        // Simple diff check to avoid re-rendering DOM if array length is same (Optional, but full render is safer for now)
        const container = document.getElementById(targetId);
        document.getElementById(countId).innerText = array.length;
        container.innerHTML = array.length > 0 ? array.map(templateFn).join("") :
            `<div class="text-center py-5 text-muted small opacity-50">لا يوجد مراجعين</div>`;
    }

    setInterval(loadMonitorData, 3000);
    loadMonitorData();
</script>

<?php include 'footer.php'; ?>