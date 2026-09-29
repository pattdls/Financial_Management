<div class="modal fade" id="deleteClientModal<?php echo $client_id; ?>" tabindex="-1" aria-labelledby="deleteClientLabel<?php echo $client_id; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteClientLabel<?php echo $client_id; ?>">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete client <strong><?php echo $client_name; ?></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="../forms_logic/delete_client.php?client_id=<?php echo $client_id; ?>" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>