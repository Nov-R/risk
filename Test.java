public class Test {
    public static void main(String[] args) {
        Publisher publisher = new Publisher();
        Observer s1 = new Subscriber("张三");
        Observer s2 = new Subscriber("李四");
        Observer s3 = new Subscriber("王五");
        Observer s4 = new Display();
        Observer s5 = new Display();
        publisher.addObserver(s1);
        publisher.addObserver(s2);
        publisher.addObserver(s3);
        publisher.addObserver(s4);
        publisher.addObserver(s5);
        
        publisher.setNews("震惊！","震惊！Java课程免费了！","2025-09-10");

        publisher.removeObserver(s3);
        publisher.removeObserver(s2);
        System.out.println("==========");
        publisher.setNews("xinwen","Youtube免费了！","2025-09-10");
        publisher.removeObserver(s3);

        Display newS4 = (Display)s4;
        newS4.fly();
    }
}
