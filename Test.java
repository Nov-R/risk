public class Test {
    public static void main(String[] args) {
        Publisher publisher = new Publisher();
        Subscriber s1 = new Subscriber("张三");
        Subscriber s2 = new Subscriber("李四");
        Subscriber s3 = new Subscriber("王五");
        publisher.addSubscriber(s1);
        publisher.addSubscriber(s2);
        publisher.addSubscriber(s3);
        
        publisher.setNews("震惊！","震惊！Java课程免费了！","2025-09-10");

        publisher.removeSubscriber(s3);
        publisher.removeSubscriber(s3);
    }
}
