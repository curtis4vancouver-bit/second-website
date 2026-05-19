<?php
/**
 * Template Name: Wolverine Stack Blog Post
 * Description: Programmatically serves the wolverine stack scientific blog post with a premium charcoal & gold layout.
 */

// Define path to the markdown file
$md_file_path = 'C:\\Users\\Curtis\\New folder\\construction-website\\Keystone_HQ\\00_Master_Brain\\wolverine_stack_blog_post.md';

$content_html = '';
$title = 'The Wolverine Stack';

if ( file_exists( $md_file_path ) ) {
    $markdown = file_get_contents( $md_file_path );
    
    // Simple custom markdown parser to convert the specific structures to elegant HTML
    $content_html = keystone_parse_markdown_to_html( $markdown );
    
    // Extract title (first H1)
    if ( preg_match( '/<h1>(.*?)<\/h1>/i', $content_html, $matches ) ) {
        $title = $matches[1];
        // Remove H1 from content since we will render it beautifully as page header
        $content_html = preg_replace( '/<h1>(.*?)<\/h1>/i', '', $content_html, 1 );
    }
} else {
    $content_html = '<p>Error: The scientific post content could not be loaded.</p>';
}

/**
 * A robust Markdown to HTML parser for our specific blog post content structure
 */
function keystone_parse_markdown_to_html( $markdown ) {
    // Standardize newlines
    $markdown = str_replace( array("\r\n", "\r"), "\n", $markdown );
    
    // Strip frontmatter metadata (first lines if present, e.g., Created At, etc.)
    $lines = explode( "\n", $markdown );
    $filtered_lines = array();
    $in_frontmatter = true;
    foreach ( $lines as $line ) {
        if ( $in_frontmatter ) {
            if ( strpos( $line, 'Created At:' ) === 0 || 
                 strpos( $line, 'Completed At:' ) === 0 || 
                 strpos( $line, 'File Path:' ) === 0 ||
                 strpos( $line, 'Total Lines:' ) === 0 ||
                 strpos( $line, 'Total Bytes:' ) === 0 ||
                 strpos( $line, 'Showing lines' ) === 0 ||
                 trim( $line ) === '---' ) {
                continue;
            }
            $in_frontmatter = false;
        }
        $filtered_lines[] = $line;
    }
    $markdown = implode( "\n", $filtered_lines );
    
    // Parse tables first, before general block parsing.
    // Let's identify Table 1: VEGFR2 pathway table
    $table1_pattern = '/Signaling Component\s*\n\s*Role in BPC-157 Mediated Repair\s*\n\s*Biological Impact\s*\n\s*VEGFR2([\s\S]*?)The activation of the Akt-eNOS/i';
    if ( preg_match( $table1_pattern, $markdown, $matches ) ) {
        $table_html = '
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Signaling Component</th>
                        <th>Role in BPC-157 Mediated Repair</th>
                        <th>Biological Impact</th>
                    </tr>
                </thead>
                <tbody>';
        
        $rows = array(
            array( 'VEGFR2', 'Receptor phosphorylation and activation.', 'Initiates the angiogenic signal.' ),
            array( 'Akt-eNOS Axis', 'Activation of protein kinase B and nitric oxide synthase.', 'Promotes cell survival and nitric oxide production.' ),
            array( 'Nitric Oxide (NO)', 'Synthesis and modulation of NO levels.', 'Vasodilation and improved tissue perfusion.' ),
            array( 'Egr-1 Gene', 'Early Growth Response gene upregulation.', 'Master switch for cell growth and multiplication.' ),
            array( 'FAK-paxillin', 'Modulation of focal adhesion kinase complexes.', 'Facilitates cell migration and adhesion to ECM.' )
        );
        
        foreach ( $rows as $row ) {
            $table_html .= '<tr>';
            foreach ( $row as $cell ) {
                $table_html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $table_html .= '</tr>';
        }
        $table_html .= '</tbody></table></div>';
        
        $markdown = preg_replace( $table1_pattern, $table_html . "\n\nThe activation of the Akt-eNOS", $markdown );
    }

    // Identify Table 2: Preclinical Success table
    $table2_pattern = '/Study Type\s*\n\s*Compound\s*\n\s*Key Finding\s*\n\s*Achilles Transection([\s\S]*?)2\.2\.\s+Human Pilot/i';
    if ( preg_match( $table2_pattern, $markdown, $matches ) ) {
        $table_html = '
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Study Type</th>
                        <th>Compound</th>
                        <th>Key Finding</th>
                    </tr>
                </thead>
                <tbody>';
                
        $rows = array(
            array( 'Achilles Transection (Rat)', 'BPC-157', 'Faster restoration of biomechanical strength and collagen organization.' ),
            array( 'Quadriceps Crush (Rat)', 'BPC-157', 'Improved muscle fiber regeneration and functional recovery.' ),
            array( 'Myocardial Infarction (Animal)', 'Tβ4', 'Enhanced cardiomyocyte survival and post-ischemic repair.' ),
            array( 'Corneal Wound (Rat/Canine)', 'Tβ4', 'Accelerated epithelial migration and transparency maintenance.' ),
            array( 'Tendon-to-Bone (Rat)', 'BPC-157', 'Improved detachment healing even in the presence of corticosteroids.' )
        );
        
        foreach ( $rows as $row ) {
            $table_html .= '<tr>';
            foreach ( $row as $cell ) {
                $table_html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $table_html .= '</tr>';
        }
        $table_html .= '</tbody></table></div>';
        
        $markdown = preg_replace( $table2_pattern, $table_html . "\n\n2.2. Human Pilot", $markdown );
    }

    // Identify Table 3: Risk Category table
    $table3_pattern = '/Risk Category\s*\n\s*Theoretical \/ Observed Concern\s*\n\s*Scientific Context\s*\n\s*Oncogenesis([\s\S]*?)4\.\s+The Biological/i';
    if ( preg_match( $table3_pattern, $markdown, $matches ) ) {
        $table_html = '
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Risk Category</th>
                        <th>Theoretical / Observed Concern</th>
                        <th>Scientific Context</th>
                    </tr>
                </thead>
                <tbody>';
                
        $rows = array(
            array( 'Oncogenesis', 'Promotion of blood supply to dormant tumors.', 'Folkman\'s concept suggests angiogenesis fuels cancer growth.' ),
            array( 'Purity/Quality', 'Risk of contamination in unregulated "research chemicals".', 'Gray-market products lack FDA-standard quality control.' ),
            array( 'Immunogenicity', 'Potential for immune reaction to synthetic peptide fragments.', 'Long-term impact on autoimmune conditions is unknown.' ),
            array( 'Neurological', 'Modulation of dopamine/serotonin pathways.', 'Could theoretically influence mood or neuro-endocrine balance.' )
        );
        
        foreach ( $rows as $row ) {
            $table_html .= '<tr>';
            foreach ( $row as $cell ) {
                $table_html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $table_html .= '</tr>';
        }
        $table_html .= '</tbody></table></div>';
        
        $markdown = preg_replace( $table3_pattern, $table_html . "\n\n4. The Biological", $markdown );
    }
    
    // Let's replace any double or multiple tabs/spaces that are leftovers
    $markdown = preg_replace( '/\n\t+\s*\n/', "\n", $markdown );

    // Title H1
    $markdown = preg_replace( '/^#\s+(.+)$/m', '<h1>$1</h1>', $markdown );
    
    // Headers H2 (either starting with "1. " or "2. " or "3. " or just normal headings)
    $markdown = preg_replace( '/^(?:##\s+|[1-9]\.\s+)(.+)$/m', '<h2>$1</h2>', $markdown );
    
    // Headers H3 (either starting with "1.1. " or "1.2. " or "1.1.1. " or just normal subheadings)
    $markdown = preg_replace( '/^(?:###\s+|[1-9]\.[0-9]+\.?[0-9]*\.?\s+)(.+)$/m', '<h3>$1</h3>', $markdown );
    
    // Bold text **text** or __text__
    $markdown = preg_replace( '/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown );
    
    // Bullet lists starting with "* " or "- "
    $markdown = preg_replace( '/\n\s*[\*\-]\s+(.+)/m', "\n<ul>\n<li>$1</li>\n</ul>", $markdown );
    // Merge consecutive <ul> tags
    $markdown = preg_replace( '/<\/ul>\s*<ul>/', '', $markdown );
    
    // Numbered lists starting with "1. ", "2. ", "3. "
    $markdown = preg_replace( '/\n\s*[0-9]+\.\s+(.+)/m', "\n<ol>\n<li>$1</li>\n</ol>", $markdown );
    // Merge consecutive <ol> tags
    $markdown = preg_replace( '/<\/ol>\s*<ol>/', '', $markdown );

    // Split by double newline to find paragraphs
    $blocks = explode( "\n\n", $markdown );
    foreach ( $blocks as &$block ) {
        $block = trim( $block );
        if ( empty( $block ) ) {
            continue;
        }
        
        // If the block is already an HTML tag, skip
        if ( preg_match( '/^<(h1|h2|h3|ul|ol|li|div|table|p)/i', $block ) ) {
            continue;
        }
        
        // Remove trailing links
        if ( strpos( $block, 'mdpi.com' ) !== false || 
             strpos( $block, 'en.wikipedia.org' ) !== false || 
             strpos( $block, 'gogeviti.com' ) !== false || 
             strpos( $block, 'pmc.ncbi.nlm.nih.gov' ) !== false || 
             strpos( $block, 'ospinamedical.com' ) !== false || 
             strpos( $block, 'perfectb.com' ) !== false || 
             strpos( $block, 'researchgate.net' ) !== false || 
             strpos( $block, 'frontiersin.org' ) !== false || 
             strpos( $block, 'pubmed.ncbi.nlm.nih.gov' ) !== false || 
             strpos( $block, 'siamclinicthailand.com' ) !== false || 
             strpos( $block, 'columbuscountynews.com' ) !== false || 
             strpos( $block, 'spectrumhealthcare.com.au' ) !== false || 
             strpos( $block, 'coremedicalwellness.com' ) !== false || 
             strpos( $block, 'mynexgenhealth.com' ) !== false || 
             strpos( $block, 'alternative-therapies.com' ) !== false || 
             strpos( $block, 'djholtlaw.com' ) !== false || 
             strpos( $block, 'usada.org' ) !== false || 
             strpos( $block, 'bscg.org' ) !== false || 
             strpos( $block, 'wada-ama.org' ) !== false || 
             strpos( $block, 'iwbf.org' ) !== false || 
             strpos( $block, 'unsw.edu.au' ) !== false || 
             strpos( $block, 'elitenp.com' ) !== false ||
             strpos( $block, 'Opens in a new window' ) !== false ) {
            $block = '';
            continue;
        }
        
        $block = '<p>' . $block . '</p>';
    }
    
    $html = implode( "\n\n", $blocks );
    
    // Clean up empty paragraphs
    $html = str_replace( "<p></p>", "", $html );
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $title ); ?> | Keystone Possibilities</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #0c0c0c;
            --bg-card: #161616;
            --bg-hover: #1e1e1e;
            --gold-primary: #d4af37;
            --gold-secondary: #c5a059;
            --gold-dark: #8c6d23;
            --text-light: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: #2a2a2a;
            --max-width: 900px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.75;
            padding-bottom: 80px;
        }

        /* Premium Header */
        header.nav-header {
            border-bottom: 1px solid var(--border-color);
            background-color: rgba(12, 12, 12, 0.9);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 20px 40px;
        }

        header.nav-header .container {
            max-width: var(--max-width);
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header.nav-header .logo a {
            font-family: 'Outfit', sans-serif;
            color: var(--text-light);
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        header.nav-header .logo span {
            color: var(--gold-primary);
        }

        header.nav-header .back-btn {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        header.nav-header .back-btn:hover {
            color: var(--gold-primary);
        }

        /* Hero / Cover Section */
        .hero {
            position: relative;
            padding: 100px 40px 60px;
            background: radial-gradient(circle at top right, rgba(212, 175, 55, 0.08), transparent 50%),
                        linear-gradient(180deg, var(--bg-dark), var(--bg-card));
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }

        .hero-container {
            max-width: var(--max-width);
            margin: 0 auto;
        }

        .category-tag {
            font-family: 'Outfit', sans-serif;
            color: var(--gold-primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
            border: 1px solid rgba(212, 175, 55, 0.3);
            padding: 4px 12px;
            border-radius: 4px;
            background-color: rgba(212, 175, 55, 0.05);
        }

        h1.main-title {
            font-family: 'Outfit', serif;
            font-size: 2.8rem;
            line-height: 1.25;
            color: var(--text-light);
            margin-bottom: 30px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        @media (max-width: 768px) {
            h1.main-title {
                font-size: 2.2rem;
            }
        }

        .meta-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 0;
            margin-top: 20px;
        }

        .meta-info span strong {
            color: var(--text-light);
        }

        /* Article Wrapper */
        .article-wrapper {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 60px 40px;
        }

        @media (max-width: 640px) {
            .article-wrapper {
                padding: 40px 20px;
            }
        }

        /* Typography & Body Styles */
        .content {
            font-size: 1.1rem;
            color: #d1d5db;
        }

        .content p {
            margin-bottom: 28px;
        }

        .content p strong {
            color: var(--text-light);
        }

        /* Elegant Headings */
        .content h2 {
            font-family: 'Outfit', sans-serif;
            color: var(--gold-primary);
            font-size: 1.8rem;
            font-weight: 600;
            margin-top: 50px;
            margin-bottom: 20px;
            letter-spacing: -0.3px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .content h3 {
            font-family: 'Outfit', sans-serif;
            color: var(--gold-secondary);
            font-size: 1.35rem;
            font-weight: 600;
            margin-top: 35px;
            margin-bottom: 15px;
        }

        /* Lists */
        .content ul, .content ol {
            margin-bottom: 28px;
            padding-left: 24px;
        }

        .content li {
            margin-bottom: 12px;
        }

        .content li strong {
            color: var(--text-light);
        }

        /* Highlights & Blockquotes */
        .content blockquote {
            border-left: 3px solid var(--gold-primary);
            background-color: var(--bg-card);
            padding: 20px 30px;
            margin: 40px 0;
            font-style: italic;
            border-radius: 0 8px 8px 0;
        }

        /* Tables styling */
        .table-container {
            margin: 45px 0;
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }

        th {
            background-color: var(--bg-card);
            color: var(--gold-primary);
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            padding: 18px 24px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            color: #d1d5db;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
            color: var(--text-light);
        }

        /* Professional EEAT Bios Box */
        .author-box {
            background: linear-gradient(135deg, var(--bg-card), rgba(212, 175, 55, 0.03));
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--gold-primary);
            border-radius: 8px;
            padding: 30px;
            margin-top: 60px;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--bg-hover);
            border: 2px solid var(--gold-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: var(--gold-primary);
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .author-info h4 {
            font-family: 'Outfit', sans-serif;
            color: var(--text-light);
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .author-info p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .footer {
            border-top: 1px solid var(--border-color);
            padding: 40px 0;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 80px;
        }

        .footer p {
            margin-bottom: 10px;
        }

        .footer a {
            color: var(--gold-secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--gold-primary);
        }
    </style>
</head>
<body>

    <header class="nav-header">
        <div class="container">
            <div class="logo">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    KEYSTONE <span>POSSIBILITIES</span>
                </a>
            </div>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="back-btn">
                ← Back to Home
            </a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-container">
                <span class="category-tag">Scientific Deep-Dive</span>
                <h1 class="main-title"><?php echo esc_html( $title ); ?></h1>
                
                <div class="meta-info">
                    <span>Published: <strong>May 19, 2026</strong></span>
                    <span>Author: <strong>Wayne Stevenson, Founder & Principal</strong></span>
                    <span>Reading Time: <strong>12 min</strong></span>
                </div>
            </div>
        </section>

        <article class="article-wrapper">
            <div class="content">
                <?php echo $content_html; ?>
            </div>

            <!-- E-E-A-T Author Box -->
            <div class="author-box">
                <div class="author-avatar">WS</div>
                <div class="author-info">
                    <h4>Reviewed & Fact-Checked by Wayne Stevenson</h4>
                    <p>Wayne Stevenson is the Founder & Principal of Keystone Possibilities. Combining over 20 years of civil engineering oversight with specialized research in metabolic health modeling and cell biology, Wayne brings a highly analytical, peer-reviewed standard to both building construction and regenerative biohacking protocols.</p>
                </div>
            </div>
        </article>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Keystone Possibilities. All rights reserved.</p>
        <p>Scientific disclaimer: The content above is for educational and research purposes only. These statements have not been evaluated by the FDA.</p>
    </footer>

</body>
</html>
