<?php
session_start();
require_once 'config/database.php';

// Function to generate slug
function generateSlug($title, $pdo) {
    // Convert to lowercase
    $slug = strtolower($title);
    
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Remove leading and trailing hyphens
    $slug = trim($slug, '-');
    
    // Check if slug exists and append number if it does
    $baseSlug = $slug;
    $counter = 1;
    
    do {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    } while ($exists);
    
    return $slug;
}

// Sample blog post data
$title = "10 Essential Digital Marketing Strategies for 2024";
$content = '<h2>Introduction</h2>
<p>In today\'s fast-paced digital landscape, staying ahead of marketing trends is crucial for business success. This comprehensive guide explores the most effective digital marketing strategies that are shaping the industry in 2024.</p>

<h2>1. Content Marketing Excellence</h2>
<p>Content remains king in the digital marketing realm. High-quality, valuable content not only attracts your target audience but also establishes your brand as an authority in your industry.</p>
<ul>
    <li>Create engaging blog posts</li>
    <li>Develop comprehensive guides</li>
    <li>Produce informative videos</li>
    <li>Design shareable infographics</li>
</ul>

<h2>2. Social Media Marketing</h2>
<p>Social media platforms continue to evolve, offering new opportunities for brands to connect with their audience. The key is to choose the right platforms and create platform-specific content.</p>
<p>Key platforms to focus on:</p>
<ul>
    <li>LinkedIn for B2B marketing</li>
    <li>Instagram for visual content</li>
    <li>TikTok for short-form video</li>
    <li>Twitter for real-time engagement</li>
</ul>

<h2>3. Search Engine Optimization (SEO)</h2>
<p>SEO remains a fundamental aspect of digital marketing. With Google\'s continuous algorithm updates, it\'s essential to stay current with best practices.</p>
<p>Key SEO elements:</p>
<ul>
    <li>Keyword research and optimization</li>
    <li>Technical SEO improvements</li>
    <li>Quality backlink building</li>
    <li>Mobile-first optimization</li>
</ul>

<h2>4. Email Marketing</h2>
<p>Email marketing continues to deliver the highest ROI among digital marketing channels. Personalization and automation are key to success.</p>
<p>Best practices include:</p>
<ul>
    <li>Segmentation of email lists</li>
    <li>Personalized content</li>
    <li>Automated email sequences</li>
    <li>A/B testing for optimization</li>
</ul>

<h2>5. Pay-Per-Click (PPC) Advertising</h2>
<p>PPC advertising allows for precise targeting and immediate results. Platforms like Google Ads and social media advertising offer powerful tools for reaching your target audience.</p>

<h2>6. Influencer Marketing</h2>
<p>Partnering with influencers can help you reach new audiences and build trust with potential customers. The key is to find influencers whose values align with your brand.</p>

<h2>7. Video Marketing</h2>
<p>Video content continues to dominate social media and search results. From short-form videos to live streams, video marketing offers numerous opportunities for engagement.</p>

<h2>8. Voice Search Optimization</h2>
<p>With the rise of voice assistants, optimizing for voice search is becoming increasingly important. Focus on natural language and question-based content.</p>

<h2>9. Artificial Intelligence in Marketing</h2>
<p>AI tools are revolutionizing marketing automation, personalization, and data analysis. Leveraging AI can help you make data-driven decisions and improve campaign performance.</p>

<h2>10. Data-Driven Marketing</h2>
<p>Using data analytics to inform your marketing decisions is crucial for success. Track and analyze key metrics to optimize your campaigns and improve ROI.</p>

<h2>Conclusion</h2>
<p>Implementing these digital marketing strategies can help your business stay competitive in 2024. Remember to continuously monitor and adjust your approach based on performance data and industry trends.</p>';

$status = 'published';
$slug = generateSlug($title, $pdo);

try {
    // Create the post
    $stmt = $pdo->prepare("
        INSERT INTO posts (title, content, status, slug, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$title, $content, $status, $slug]);
    
    echo "Sample blog post created successfully with slug: " . $slug;
} catch (PDOException $e) {
    echo "Error creating sample post: " . $e->getMessage();
}
?> 