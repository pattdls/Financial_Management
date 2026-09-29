<?php
// This file is included for each project in Not Yet Started, Ongoing, and Completed sections
// Variables available: $row, $phases, $progress, $completedWeight, $totalWeight, $phaseWeights
?>
<div class="col-lg-4 col-md-6 mb-4">
    <!-- Project Card -->
    <div class="card h-100" style="border-radius: 12px;">
        <div class="card-body d-flex flex-column p-4">

            <?php
            $phases = $row['phases'];
            $progress = $row['progress'];
            $completedWeight = $row['completedWeight'];
            ?>

            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="card-title fw-bold mb-1"><?= htmlspecialchars($row['project_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($row['po_num']) ?> | <?= htmlspecialchars($row['project_type'] ?? 'project_type') ?></small>
                </div>
                <?php
                // Determine badge based on project's category
                if (isset($row['project_category'])) {
                    // Use the category we set during filtering
                    switch($row['project_category']) {
                        case 'not_yet_started':
                            $badge_text = 'NOT YET STARTED';
                            $badge_class = 'bg-secondary';
                            break;
                        case 'ongoing':
                            $badge_text = 'ONGOING';
                            $badge_class = 'bg-success';
                            break;
                        case 'completed':
                            $badge_text = 'COMPLETED';
                            $badge_class = 'bg-primary';
                            break;
                        default:
                            $badge_text = 'ONGOING';
                            $badge_class = 'bg-success';
                    }
                } else {
                    // Fallback to old logic if category not set
                    if ($totalWeight > 0 && $completedWeight === 0) {
                        $badge_text = 'NOT YET STARTED';
                        $badge_class = 'bg-secondary';
                    } elseif ($totalWeight > 0 && $completedWeight === $totalWeight) {
                        $badge_text = 'COMPLETED';
                        $badge_class = 'bg-primary';
                    } else {
                        $badge_text = 'ONGOING';
                        $badge_class = 'bg-success';
                    }
                }
                ?>
                <span class="badge <?= $badge_class ?> px-3 py-2 rounded-pill"><?= $badge_text ?></span>
            </div>

            <div class="mb-2">
                <h6 class="fw-semibold mb-2">Overall Progress</h6>
                <div class="progress" style="height: 10px; border-radius: 10px;">
                    <div class="progress-bar bg-success" role="progressbar"
                        style="width: <?= $progress ?>%; border-radius: 10px;"
                        aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-end mt-1">
                    <small class="fw-bold"><?= $progress ?>%</small>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-semibold mb-3">Project Status</h6>
                <div class="status-list">
                    <?php if (empty($phases)): ?>
                        <div class="text-muted small">No phases found for this project.</div>
                    <?php else: ?>
                        <?php foreach ($phases as $phase): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <?php if ($phase['status'] === 'Completed'): ?>
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?php elseif ($phase['status'] === 'In Progress'): ?>
                                        <i class="bi bi-arrow-repeat text-primary me-2"></i>
                                    <?php else: ?>
                                        <i class="bi bi-clock-fill text-warning me-2"></i>
                                    <?php endif; ?>
                                    <span class="small"><?= htmlspecialchars($phase['phase_name']) ?></span>
                                </div>
                                <?php if ($phase['status'] === 'Completed'): ?>
                                    <span class="badge bg-light text-primary small">Completed</span>
                                <?php elseif ($phase['status'] === 'In Progress'): ?>
                                    <span class="badge bg-primary text-light small">In Progress</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark small">Pending</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-6">
                    <small class="text-muted d-block">Start Date</small>
                    <span class="fw-semibold"><?= date('M j, Y', strtotime($row['start_date'])) ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Expected End Date</small>
                    <span class="fw-semibold"><?= date('M j, Y', strtotime($row['end_date'])) ?></span>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-6">
                    <small class="text-muted d-block">Project Value</small>
                    <span class="fw-semibold">₱ <?= number_format($row['projected_budget_cost'], 2, '.', ',') ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Area</small>
                    <span class="fw-semibold"><?= htmlspecialchars($row['project_type']) ?></span>
                </div>
            </div>

            <div class="mt-auto">
                <!-- View Details Button triggers modal -->
                <button type="button"
                    class="btn btn-primary btn-sm rounded-pill px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#projectDetailsModal<?= $row['project_id'] ?>">
                    View Details
                </button>
            </div>
        </div>
    </div>

    <!-- Project Details Modal -->
    <div class="modal fade" id="projectDetailsModal<?= $row['project_id'] ?>" tabindex="-1" aria-labelledby="projectDetailsModalLabel<?= $row['project_id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg project-details-modal">
            <div class="modal-content rounded-4 shadow-lg">
                <!-- Modal Header -->
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="projectDetailsModalLabel<?= $row['project_id'] ?>">
                            <?= htmlspecialchars($row['project_name']) ?>
                        </h5>
                        <small class="text-muted">Project Details & Progress</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body py-4">
                    <!-- Project Overview -->
                    <div class="row mb-4 project-overview">
                        <div class="col-6">
                            <div class="detail-card">
                                <small class="text-muted d-block mb-1">PO Number</small>
                                <span class="fw-semibold"><?= htmlspecialchars($row['po_num']) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="detail-card">
                                <small class="text-muted d-block mb-1">Project Type</small>
                                <span class="fw-semibold"><?= htmlspecialchars($row['project_type'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Project Phases Section -->
                    <?php
                    $allLogsByPhase = [];
                    $logs_query = $conn->prepare("SELECT phase_name, status, description, files, updated_at FROM project_phase_logs WHERE project_id = ? ORDER BY updated_at DESC");
                    $logs_query->bind_param("i", $row['project_id']);
                    $logs_query->execute();
                    $logs_result = $logs_query->get_result();
                    while ($log = $logs_result->fetch_assoc()) {
                        $allLogsByPhase[$log['phase_name']][] = $log;
                    }
                    ?>
                    <div class="section-container mb-4">
                        <h6 class="section-title fw-bold mb-3">
                            <i class="bi bi-diagram-3 text-primary me-2"></i>
                            Project Phases
                        </h6>

                        <?php if (empty($phases)): ?>
                            <div class="empty-state text-center py-3">
                                <i class="bi bi-info-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No phases found for this project.</p>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="phasesAccordion<?= $row['project_id'] ?>">
                                <?php foreach ($phases as $index => $phase):
                                    $phase_descriptions = [
                                        "Design & Permits" => "Development of detailed plans, drawings, and technical specifications, followed by securing all necessary permits and regulatory clearances.",
                                        "Material Procurement" => "Acquisition and delivery of all specified materials, tools, and equipment required for the project.",
                                        "Site Preparation" => "Preparation of the project site including mobilization, safety setups, and initial clearing.",
                                        "Project Installation" => "Execution of all construction and installation activities as per the project scope.",
                                        "Final Inspection" => "Comprehensive evaluation of completed works to ensure compliance with project specifications and quality standards."
                                    ];
                                    $desc = $phase_descriptions[$phase['phase_name']] ?? '';
                                    $collapseId = "collapsePhase{$row['project_id']}{$index}";
                                    $headingId = "headingPhase{$row['project_id']}{$index}";
                                    $phaseName = $phase['phase_name'];
                                ?>
                                    <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
                                        <h2 class="accordion-header" id="<?= $headingId ?>">
                                            <button class="accordion-button collapsed d-flex justify-content-between align-items-center"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?= $collapseId ?>"
                                                aria-expanded="false"
                                                aria-controls="<?= $collapseId ?>">

                                                <!-- Phase Title -->
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary rounded-pill me-3"><?= $index + 1 ?></span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($phase['phase_name']) ?></span>
                                                </div>
                                            </button>
                                        </h2>

                                        <div id="<?= $collapseId ?>" class="accordion-collapse collapse"
                                            aria-labelledby="<?= $headingId ?>"
                                            data-bs-parent="#phasesAccordion<?= $row['project_id'] ?>">
                                            <div class="accordion-body">

                                                <!-- Phase Description -->
                                                <?php if (!empty($desc)): ?>
                                                    <p class="text-muted small mb-3"><?= $desc ?></p>
                                                <?php endif; ?>

                                                <!-- Progress logs for this phase -->
                                                <?php if (!empty($allLogsByPhase[$phaseName])): ?>
                                                    <div class="fs-6 fw-bold mb-2">Progress Updates</div>
                                                    <ul class="list-unstyled ms-2">
                                                        <?php foreach ($allLogsByPhase[$phaseName] as $log): ?>
                                                            <li class="fs-6 mb-2">
                                                                <div class="small mt-1">
                                                                    <i class="bi bi-clock me-1"></i>
                                                                    <strong><?= date('M j, Y', strtotime($log['updated_at'])) ?>:</strong>
                                                                    <?php if (!empty($log['status'])): ?>
                                                                        <?php if ($log['status'] === 'In Progress'): ?>
                                                                            Phase set to <span class="text-primary fw-semibold">In Progress</span>.
                                                                        <?php elseif ($log['status'] === 'Completed'): ?>
                                                                            Phase marked as <span class="text-success fw-semibold">Completed</span>.
                                                                        <?php elseif ($log['status'] === 'Pending'): ?>
                                                                            Phase set to <span class="text-warning fw-semibold">Pending</span>.
                                                                        <?php else: ?>
                                                                            <?= nl2br(htmlspecialchars($log['description'])) ?>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if (!empty($log['files'])):
                                                                    $files = json_decode($log['files'], true);
                                                                    if ($files): ?>
                                                                        <div class="ms-4 mb-3">
                                                                            <span class="text-muted fs-6">Photos/Documents:</span>
                                                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                                                <?php foreach ($files as $file):
                                                                                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                                                        <a href="<?= htmlspecialchars($file) ?>" target="_blank">
                                                                                            <img src="<?= htmlspecialchars($file) ?>" alt="Progress Photo"
                                                                                                style="width:80px; height:80px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">
                                                                                        </a>
                                                                                    <?php else: ?>
                                                                                        <a href="<?= htmlspecialchars($file) ?>" target="_blank" class="badge bg-secondary">
                                                                                            <?= basename($file) ?>
                                                                                        </a>
                                                                                <?php endif;
                                                                                endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                <?php endif;
                                                                endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-muted small">No progress logs yet.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Project Add-ons Section -->
                    <?php
                    $addon_query_modal = $conn->prepare("SELECT * FROM project_addons WHERE project_id = ?");
                    $addon_query_modal->bind_param("i", $row['project_id']);
                    $addon_query_modal->execute();
                    $addon_result_modal = $addon_query_modal->get_result();
                    ?>

                    <?php if ($addon_result_modal->num_rows > 0): ?>
                        <div class="section-container">
                            <h6 class="section-title fw-bold mb-3 text-success">
                                <i class="bi bi-plus-circle text-success me-2"></i>
                                Project Add-ons
                                <span class="badge bg-success bg-opacity-10 text-success ms-2 px-2 py-1 rounded-pill small">
                                    <?= $addon_result_modal->num_rows ?>
                                </span>
                            </h6>

                            <div class="addons-list">
                                <?php while ($addon = $addon_result_modal->fetch_assoc()): ?>
                                    <div class="addon-item border rounded-3 p-3 mb-3 bg-opacity-5">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="addon-title fw-semibold mb-0 text-success me-3">
                                                        <i class="bi bi-puzzle me-2"></i>
                                                        <?= htmlspecialchars($addon['addon_title'] ?? $addon['addon_name']) ?>
                                                    </h6>
                                                    <!-- Add-on Status Badge -->
                                                    <div class="addon-status">
                                                        <?php
                                                        $status = strtolower($addon['status'] ?? 'upcoming');
                                                        if ($status === 'completed'): ?>
                                                            <span class="badge bg-success px-3 py-2 rounded-pill">
                                                                <i class="bi bi-check-circle me-1"></i>Completed
                                                            </span>
                                                        <?php elseif ($status === 'ongoing' || $status === 'in progress'): ?>
                                                            <span class="badge bg-primary px-3 py-2 rounded-pill">
                                                                <i class="bi bi-arrow-repeat me-1"></i>Ongoing
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning px-3 py-2 rounded-pill">
                                                                <i class="bi bi-clock me-1"></i>Upcoming
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($addon['contract_file'])): ?>
                                                <a href="<?= htmlspecialchars($addon['contract_file']) ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                    <i class="bi bi-file-earmark-text me-1"></i>View Contract
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary px-3 py-2 rounded-pill">No Contract</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="addon-details">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="detail-item">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="bi bi-hash text-muted me-1"></i>PO Number
                                                        </small>
                                                        <span class="fw-medium"><?= htmlspecialchars($addon['po_number']) ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="detail-item">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="bi bi-calendar-event text-muted me-1"></i>End Date
                                                        </small>
                                                        <span class="fw-medium"><?= date('M j, Y', strtotime($addon['end_date'])) ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="detail-item">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="bi bi-currency-dollar text-muted me-1"></i>Budget
                                                        </small>
                                                        <span class="fw-semibold text-success">₱<?= number_format($addon['projected_budget_cost'], 2) ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="detail-item">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="bi bi-tag text-muted me-1"></i>Type
                                                        </small>
                                                        <span class="fw-medium"><?= htmlspecialchars($addon['project_type']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>