-- 风险表
CREATE TABLE IF NOT EXISTS risks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    probability INT NOT NULL CHECK (probability BETWEEN 1 AND 5),
    impact INT NOT NULL CHECK (impact BETWEEN 1 AND 5),
    status ENUM('identified', 'analyzing', 'mitigating', 'monitoring', 'closed') NOT NULL,
    mitigation TEXT,
    contingency TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 反馈表
CREATE TABLE IF NOT EXISTS feedbacks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    risk_id INT NOT NULL,
    content TEXT NOT NULL,
    type ENUM('comment', 'assessment', 'mitigation_proposal', 'status_update') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (risk_id) REFERENCES risks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 节点表
CREATE TABLE IF NOT EXISTS nodes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    risk_id INT,
    feedback_id INT,
    type ENUM('risk_review', 'feedback_review') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewer VARCHAR(255) NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (risk_id) REFERENCES risks(id) ON DELETE CASCADE,
    FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE,
    CHECK (
        (risk_id IS NOT NULL AND feedback_id IS NULL AND type = 'risk_review') OR
        (feedback_id IS NOT NULL AND risk_id IS NULL AND type = 'feedback_review')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 索引
CREATE INDEX idx_risks_status ON risks(status);
CREATE INDEX idx_feedbacks_risk_id ON feedbacks(risk_id);
CREATE INDEX idx_feedbacks_status ON feedbacks(status);
CREATE INDEX idx_nodes_risk_id ON nodes(risk_id);
CREATE INDEX idx_nodes_feedback_id ON nodes(feedback_id);
CREATE INDEX idx_nodes_status ON nodes(status);
